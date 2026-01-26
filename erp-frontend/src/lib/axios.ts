import axios from 'axios';

// Explicitly define the backend
const BASE_URL = 'http://localhost:8000';

const api = axios.create({
    baseURL: BASE_URL,
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

// We force the URL to be absolute for EVERY request.
// This prevents the browser from defaulting to port 3000.
api.interceptors.request.use((config) => {
    if (config.url && !config.url.startsWith('http')) {
        const cleanUrl = config.url.startsWith('/') ? config.url : `/${config.url}`;
        config.url = `${BASE_URL}${cleanUrl}`;
    }
    
    const warehouseId = localStorage.getItem('selected_warehouse_id');
    if (warehouseId) {
        config.headers['X-Warehouse-Id'] = warehouseId;
    }
    return config;
});

export default api;