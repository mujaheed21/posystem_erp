'use client';

import React, { useState, useEffect } from 'react';
import { createSale } from '../../../lib/api/sales';
import { useAuthStore } from '../../../store/useAuthStore';
import { Alert, Button, InputNumber, Form, Typography, message, Card, Space, Divider } from 'antd';
import { ShoppingCartOutlined, CalculatorOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export default function CreateSalePage() {
    const { user } = useAuthStore();
    const [loading, setLoading] = useState(false);
    const [form] = Form.useForm();
    
    // Watch fields for real-time total calculation in the UI
    const qty = Form.useWatch('quantity', form) || 0;
    const price = Form.useWatch('unit_price', form) || 0;
    const subtotal = qty * price;

    useEffect(() => {
        // Automatically link the form to the user's location and set defaults
        if (user?.warehouse_id) {
            form.setFieldsValue({ 
                warehouse_id: user.warehouse_id,
                unit_price: 150.00, 
                quantity: 1
            });
        }
    }, [user, form]);

    const onFinish = async (values: any) => {
        setLoading(true);
        const finalAmount = values.quantity * values.unit_price;
        
        // Structured payload to satisfy Laravel's strict validation
        const payload = {
            warehouse_id: values.warehouse_id,
            subtotal: finalAmount,
            total: finalAmount,
            tax_amount: 0,
            status: 'completed',
            items: [
                {
                    product_id: 1, 
                    quantity: values.quantity,
                    unit_price: values.unit_price,
                    subtotal: finalAmount,
                    total_price: finalAmount // Fixes the "items.0.total_price required" error
                }
            ]
        };

        try {
            await createSale(payload);
            message.success('Sale Processed & Stock Deducted!');
            form.resetFields(['quantity']);
        } catch (error: any) {
            const errorData = error.response?.data;
            const errorMsg = errorData?.message || 'Validation Error';
            
            message.error(`Backend Error: ${errorMsg}`);
            
            // Logs specific validation failures (e.g., missing fields) to the browser console
            if (errorData?.errors) {
                console.error("Laravel Validation Details:", errorData.errors);
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-2xl mx-auto p-4">
            <Card className="shadow-md border-0 ring-1 ring-slate-200">
                <div className="flex items-center gap-3 mb-6">
                    <ShoppingCartOutlined className="text-2xl text-blue-600" />
                    <Title level={3} style={{ margin: 0 }}>New Sale Transaction</Title>
                </div>

                {!user?.warehouse_id && (
                    <Alert 
                        message="Location Missing" 
                        description="Your user profile is not linked to a Business Location. Transaction disabled."
                        type="error" 
                        showIcon 
                        className="mb-6"
                    />
                )}

                <Form form={form} layout="vertical" onFinish={onFinish}>
                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item label="Active Location (ID)" name="warehouse_id">
                            <InputNumber className="w-full" disabled />
                        </Form.Item>
                        
                        <Form.Item label="Transaction Status" name="status" initialValue="completed">
                            <div className="p-2 bg-green-50 text-green-700 rounded border border-green-200 text-center font-medium">
                                COMPLETED
                            </div>
                        </Form.Item>
                    </div>

                    <Divider orientation="left">Item Details (Product #1)</Divider>
                    
                    <div className="grid grid-cols-3 gap-4 bg-slate-50 p-4 rounded-lg border border-slate-100 mb-6">
                        <Form.Item label="Quantity" name="quantity" rules={[{required: true}]}>
                            <InputNumber min={1} className="w-full" />
                        </Form.Item>
                        
                        <Form.Item label="Unit Price" name="unit_price" rules={[{required: true}]}>
                            <InputNumber precision={2} className="w-full" prefix="₦" />
                        </Form.Item>

                        <div className="flex flex-col justify-center">
                            <Text type="secondary" size="small">Row Subtotal</Text>
                            <Text strong className="text-lg">₦{subtotal.toLocaleString()}</Text>
                        </div>
                    </div>

                    <div className="bg-blue-50 p-4 rounded-lg flex justify-between items-center mb-6">
                        <Space>
                            <CalculatorOutlined className="text-blue-600" />
                            <Text strong>Total Payable:</Text>
                        </Space>
                        <Title level={2} style={{ margin: 0, color: '#1d4ed8' }}>
                            ₦{subtotal.toLocaleString()}
                        </Title>
                    </div>

                    <Button 
                        type="primary" 
                        htmlType="submit" 
                        size="large" 
                        block 
                        loading={loading}
                        disabled={!user?.warehouse_id}
                        className="h-12 text-lg font-semibold"
                    >
                        {loading ? 'Submitting Ledger Entry...' : 'Finalize Sale'}
                    </Button>
                </Form>
            </Card>
        </div>
    );
}