import api from '../lib/axios';

/**
 * 5. Backend Connectivity Handshake
 * Ensures the frontend is "blessed" with a CSRF cookie from Laravel.
 */
export const initializeCsrf = async () => {
    return await api.get('/sanctum/csrf-cookie');
};

export const login = async (credentials: any) => {
    // Standard procedure: Get cookie first, then post credentials
    await initializeCsrf();
    return await api.post('/api/v1/login', credentials);
};