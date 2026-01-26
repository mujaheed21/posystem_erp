import { create } from 'zustand';
import api from '../lib/axios';

interface AuthState {
    user: any | null;
    isAuthenticated: boolean;
    currentWarehouseId: number | null;
    setAuth: (user: any, warehouseId?: number) => void;
    logout: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
    user: null,
    isAuthenticated: false,
    currentWarehouseId: localStorage.getItem('selected_warehouse_id') 
        ? Number(localStorage.getItem('selected_warehouse_id')) 
        : null,

    setAuth: (user, warehouseId) => {
        // 1. Determine the ID: Use passed ID OR extract from user JSON
        const activeId = warehouseId || user.business_location_id || null;

        if (activeId) {
            localStorage.setItem('selected_warehouse_id', activeId.toString());
        }

        // 2. We attach warehouse_id to the user object as well for component compatibility
        set({ 
            user: { ...user, warehouse_id: activeId }, 
            isAuthenticated: true, 
            currentWarehouseId: activeId 
        });
    },

    logout: async () => {
        try {
            await api.post('/api/v1/logout');
        } catch (error) {
            console.warn("Session already invalidated on server");
        } finally {
            localStorage.removeItem('selected_warehouse_id');
            set({ 
                user: null, 
                isAuthenticated: false, 
                currentWarehouseId: null 
            });
        }
    }
}));