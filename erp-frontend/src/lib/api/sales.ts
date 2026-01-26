import api from '../api'; // Or your axios instance path

export const createSale = async (payload: any) => {
    // We send the payload directly so Laravel receives exactly what the form built
    return api.post('/api/v1/sales', {
        ...payload,
        business_location_id: payload.warehouse_id // Mapping for backend consistency
    });
};