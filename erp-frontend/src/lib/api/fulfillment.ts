import api from '../api';
export const submitFulfillmentScan = async (token: string) => {
    try {
        const response = await api.post('/api/v1/fulfillments/scan', { token });
        return { success: true, data: response.data };
    } catch (error: any) {
        // We capture the exact error message from the Laravel FulfillmentService
        return { 
            success: false, 
            message: error.response?.data?.message || 'Verification failed' 
        };
    }
};