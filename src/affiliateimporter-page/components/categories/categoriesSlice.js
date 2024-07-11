import { createSlice } from '@reduxjs/toolkit';
import { fetchCategories } from '../../services/apiService';

export const categoriesSlice = createSlice({
	name: 'categories',
	initialState: {
		categories: [],
		isLoading: false,
		error: null
	},
	extraReducers: (builder) => {
		builder.addCase(fetchCategories.pending, (state) => {
			state.isLoading = true;
		}),
		builder.addCase(fetchCategories.fulfilled, (state, action) => {
			state.isLoading = false;
			state.categories = action.payload;
		}),
		builder.addCase(fetchCategories.rejected, (state, action) => {
			state.isLoading = false;
			state.error = action.error.message;
		})
	}
})

export default categoriesSlice.reducer