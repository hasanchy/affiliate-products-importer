<?php
/**
 * Products Endpoint.
 */

namespace AFFPRODIMP\App\Endpoints\V1;

// Avoid direct file request
defined( 'ABSPATH' ) || die( 'No direct access allowed!' );

use AFFPRODIMP\Core\Endpoint;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

class Products extends Endpoint {
	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $endpoint = 'products';

	/**
	 * Register the routes for handling products functionality.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			$this->get_endpoint(),
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'edit_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_products' ),
					'permission_callback' => array( $this, 'edit_permission' ),
				),
			)
		);
	}

	/**
	 * Handle the request to get products.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 * @since 1.0.0
	 */
	public function get_products( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-NONCE' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( 'Invalid nonce', 403 );
		}

		// Get and sanitize request parameters
		$paged          = (int) $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;
		$posts_per_page = (int) $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 50;

		// Set up query arguments
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'meta_key'       => 'affprodimp_amz_asin',
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
		);

		// Execute query
		$query         = new WP_Query( $args );
		$total_results = $query->found_posts;

		$products = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$product    = array();
				$product_id = get_the_ID();

				// Populate product data
				$product['product_id']          = $product_id;
				$product['product_url']         = esc_url( get_permalink( $product_id ) );
				$product['product_title']       = esc_html( get_the_title( $product_id ) );
				$product['product_import_date'] = esc_html( get_the_date( '', $product_id ) );
				$product['product_asin']        = esc_html( get_post_meta( $product_id, 'affprodimp_amz_asin', true ) );
				$product['key']                 = $product_id;

				// Get product image
				$thumbnail_id             = get_post_thumbnail_id( $product_id ) ? get_post_thumbnail_id( $product_id ) : $product_id;
				$image_primary            = wp_get_attachment_image_src( $thumbnail_id );
				$product['image_primary'] = esc_url( $image_primary[0] );

				// Get sync last date
				$sync_last_date            = get_post_meta( $product_id, 'affprodimp_sync_last_date', true );
				$product['sync_last_date'] = $sync_last_date ? $this->time_ago( $sync_last_date ) : $this->time_ago( strtotime( $product['product_import_date'] ) );

				$products[] = $product;
			}
			wp_reset_postdata();
		}

		// Prepare response
		$response = array(
			'products' => $products,
			'total'    => $total_results,
			'page'     => (int) $paged,
		);

		return new WP_REST_Response( $response );
	}

	/**
	 * Calculate the time difference in human-readable format.
	 *
	 * @param int $time The timestamp.
	 * @return string
	 * @since 1.0.0
	 */
	public function time_ago( $time ) {
		$diff = time() - $time;

		if ( $diff < 60 ) {
			return $diff <= 5 ? 'Just now' : "$diff seconds ago";
		}

		$minutes = round( $diff / 60 );
		if ( $minutes < 60 ) {
			return 1 === $minutes ? 'One minute ago' : "$minutes minutes ago";
		}

		$hours = round( $diff / 3600 );
		if ( $hours < 24 ) {
			return 1 === $hours ? 'An hour ago' : "$hours hours ago";
		}

		$days = round( $diff / 86400 );
		if ( $days < 7 ) {
			return 1 === $days ? 'Yesterday' : "$days days ago";
		}

		$weeks = round( $diff / 604800 );
		if ( $weeks < 4.3 ) {
			return 1 === $weeks ? 'A week ago' : "$weeks weeks ago";
		}

		$months = round( $diff / 2600640 );
		if ( $months < 12 ) {
			return 1 === $months ? 'A month ago' : "$months months ago";
		}

		$years = round( $diff / 31207680 );
		return 1 === $years ? 'One year ago' : "$years years ago";
	}

	/**
	 * Handle the request to get products.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 * @since 1.0.0
	 */
	public function save_products( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-NONCE' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( 'Invalid nonce', 403 );
		}

		$products   = $request->get_param( 'products' );
		$categories = $request->get_param( 'categories' );

		// Sanitize categories
		if ( ! is_array( $categories ) ) {
			$categories = array();
		} else {
			$categories = array_map( 'intval', $categories ); // Make sure categories are integers
		}

		$product_ids   = array();
		$product_asins = array();

		foreach ( $products as $product ) {
			// Sanitize product fields
			$asin          = isset( $product['asin'] ) ? sanitize_text_field( $product['asin'] ) : '';
			$post_title    = isset( $product['post_title'] ) ? sanitize_text_field( $product['post_title'] ) : '';
			$post_name     = isset( $product['post_name'] ) ? sanitize_title( $product['post_name'] ) : '';
			$post_content  = isset( $product['post_content'] ) ? wp_kses_post( $product['post_content'] ) : '';
			$image_primary = isset( $product['image_primary'] ) ? esc_url_raw( $product['image_primary'] ) : '';
			$regular_price = isset( $product['regular_price'] ) ? floatval( $product['regular_price'] ) : 0;
			$sale_price    = isset( $product['sale_price'] ) ? floatval( $product['sale_price'] ) : '';
			$product_url   = isset( $product['product_url'] ) ? esc_url_raw( $product['product_url'] ) : '';

			// Ensure essential fields are present
			if ( empty( $asin ) || empty( $post_title ) || empty( $post_name ) ) {
				continue; // Skip this product if essential data is missing
			}

			$new_post = array(
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_status'  => 'publish',
				'post_date'    => current_time( 'mysql' ),
				'post_author'  => get_current_user_id(), // Consider changing this to a variable if the author ID should vary
				'post_type'    => 'product',
				'post_name'    => $post_name,
			);

			$post_id         = wp_insert_post( $new_post );
			$product_ids[]   = $post_id;
			$product_asins[] = $asin;

			/*===================Update product categories=======================*/
			if ( ! empty( $categories ) ) {
				wp_set_post_terms( $post_id, $categories, 'product_cat' );
			}

			/*===================Update product type=======================*/
			wp_set_object_terms( $post_id, 'external', 'product_type' );

			/*===================Update product Images=======================*/
			$remote_image = get_option( 'affprodimp_settings_remote_image' );

			if ( 'No' === $remote_image ) {
				if ( ! function_exists( 'media_sideload_image' ) ) {
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
				}
				if ( ! empty( $image_primary ) ) {
					$thumbnail_image_id = media_sideload_image( $image_primary, $post_id, $post_title, 'id' );
					if ( ! is_wp_error( $thumbnail_image_id ) ) {
						set_post_thumbnail( $post_id, $thumbnail_image_id );
					}
				}
			} elseif ( ! empty( $image_primary ) ) {
					update_post_meta( $post_id, 'affprodimp_product_img_url', $image_primary );
			}

			/*===================Update product ASIN=======================*/
			update_post_meta( $post_id, 'affprodimp_amz_asin', $asin );

			/*===================Update product price=======================*/
			$price = ! empty( $sale_price ) ? $sale_price : $regular_price;
			update_post_meta( $post_id, '_price', $price );
			update_post_meta( $post_id, '_regular_price', $regular_price );

			if ( ! empty( $sale_price ) ) {
				update_post_meta( $post_id, '_sale_price', $sale_price );
			}

			/*===================Update product url=======================*/
			if ( ! empty( $product_url ) ) {
				update_post_meta( $post_id, '_product_url', $product_url );
			}
		}

		$return = array(
			'product_ids'   => array_map( 'intval', $product_ids ),
			'product_asins' => array_map( 'sanitize_text_field', $product_asins ),
		);

		return new WP_REST_Response( $return );
	}
}
