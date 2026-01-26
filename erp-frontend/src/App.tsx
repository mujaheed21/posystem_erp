import { useEffect, useState } from 'react';
import { ConfigProvider, App as AntApp, Spin } from 'antd';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import MainLayout from './components/MainLayout';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';
import ProtectedRoute from './components/ProtectedRoute';
import api from './lib/axios';
import { useAuthStore } from './store/useAuthStore';

// Import New Feature Components
import FulfillmentScanner from './components/fulfillment/FulfillmentScanner';
import CreateSalePage from './app/sales/create/page'; 

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

const themeConfig = {
  token: {
    colorPrimary: '#1677ff',
    borderRadius: 4,
    fontFamily: 'Inter, system-ui, sans-serif',
  },
};

function App() {
  const { setAuth, logout } = useAuthStore();
  const [isInitializing, setIsInitializing] = useState(true);

  useEffect(() => {
    const initializeAuth = async () => {
      try {
        // 1. Fetch the user from Laravel Sanctum
        const response = await api.get('/api/user');
        
        if (response.data) {
          // 2. Pass the data to the store. 
          // The store now handles mapping business_location_id -> warehouse_id
          setAuth(response.data);
        }
      } catch (error) {
        // If 401 or network error, clear the local state
        logout();
      } finally {
        // 3. Stop the spinner so routes can render
        setIsInitializing(false);
      }
    };

    initializeAuth();
  }, [setAuth, logout]);

  // Loading state prevents the "Login Page Flash" during session verification
  if (isInitializing) {
    return (
      <div className="flex h-screen w-screen items-center justify-center bg-slate-50">
        <Spin size="large" tip="Securing Session..." />
      </div>
    );
  }

  return (
    <QueryClientProvider client={queryClient}>
      <ConfigProvider theme={themeConfig}>
        <AntApp>
          <Router>
            <Routes>
              {/* Public Route */}
              <Route path="/login" element={<Login />} />

              {/* Guarded Routes */}
              <Route element={<ProtectedRoute />}>
                <Route element={<MainLayout />}>
                  {/* Dashboard / Home */}
                  <Route path="/" element={<Dashboard />} />
                  
                  {/* Fulfillment Logic */}
                  <Route path="/fulfillment/scan" element={<FulfillmentScanner />} />
                  
                  {/* Sales Logic */}
                  <Route path="/sales/create" element={<CreateSalePage />} />

                  {/* Reports & Other */}
                  <Route path="/reports/profit-loss" element={<div className="p-4">Reports</div>} />
                </Route>
              </Route>

              {/* Catch-all: Redirect to Dashboard */}
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </Router>
        </AntApp>
      </ConfigProvider>
    </QueryClientProvider>
  );
}

export default App;