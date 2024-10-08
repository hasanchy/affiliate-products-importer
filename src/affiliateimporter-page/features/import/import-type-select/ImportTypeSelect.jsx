import React from 'react';
import { Card, Row, Col, Flex, Button, Space } from 'antd';
import ImportTypeCards from './ImportTypeCards';
import { CopyOutlined } from '@ant-design/icons';

import { setImportType, setImportStepNext } from '../importSlice';
import { useDispatch, useSelector } from 'react-redux';
import { __ } from '@wordpress/i18n';


const ImportTypeSelect = () => {

    const dispatch = useDispatch();
    const { importType, importStepIndex } = useSelector((state) => state.import);

    const handleImportTypeSelect = (type) => {
        dispatch(setImportType(type));
    }

    const handleImportStep = () => {
        dispatch(setImportStepNext());
    }

    if(importStepIndex===0){
        return (
            <React.Fragment>
                <Card>
                    <Space
                        direction='vertical'
                        size="large"
                        style={{
                            display: 'flex',
                        }}
                    >
                        <ImportTypeCards
                            defaultSelectedKey={importType}
                            items={[
                                {
                                    key: 'copy-paste',
                                    label: __( 'Copy/Paste Import', 'affiliate-products-importer' ),
                                    icon: <CopyOutlined/>
                                }
                            ]}
                            onClick={handleImportTypeSelect}
                        />
                        <Row>
                            <Col span={12}>
                            </Col>
                            <Col span={12}>
                                <Flex justify='flex-end'>
                                    <Button type="primary" disabled={!importType} onClick={handleImportStep.bind(this,1)}>{ __( 'Next', 'affiliate-products-importer' ) }</Button>
                                </Flex>
                            </Col>
                        </Row>
                    </Space>
                </Card>
            </React.Fragment>
        )
    }else{
        return null;
    }
}

export default ImportTypeSelect;