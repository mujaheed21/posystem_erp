import axios from 'axios';

const BASE_URL = 'http://localhost:8000';

const api = axios.create({
    baseURL: BASE_URL,
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
});

api.interceptors.request.use((config) => {
    // Force absolute URL to prevent port 3000 defaults
    if (config.url && !config.url.startsWith('http')) {
        const cleanUrl = config.url.startsWith('/') ? config.url : `/${config.url}`;
        config.url = `${BASE_URL}${cleanUrl}`;
    }
    
    // Inject Warehouse Context
    const warehouseId = localStorage.getItem('selected_warehouse_id');
    if (warehouseId) {
        config.headers['X-Warehouse-Id'] = warehouseId;
    }
    return config;
});

export default api;