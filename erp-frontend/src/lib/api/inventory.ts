import api from '../api';

export const getInventoryDashboard = async () => {
    try {
        const [alerts, valuation] = await Promise.all([
            api.get('/api/v1/inventory/alerts'),
            api.get('/api/v1/inventory/valuation')
        ]);
        return {
            alerts: alerts.data,
            valuation: valuation.data
        };
    } catch (error) {
        console.error('Failed to fetch inventory data', error);
        throw error;
    }
};