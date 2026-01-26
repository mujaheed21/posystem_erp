import React, { useState } from 'react';
import { Form, Input, Button, Typography, message, Space } from 'antd';
import { MailOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate, Link } from 'react-router-dom';
import api from '../lib/axios';
import { useAuthStore } from '../store/useAuthStore';
import loginHero from '../assets/login-hero.png'; 

const { Title, Text } = Typography;

const Login: React.FC = () => {
  const navigate = useNavigate();
  const setAuth = useAuthStore((state) => state.setAuth);
  const [loading, setLoading] = useState(false);

  const onFinish = async (values: any) => {
    setLoading(true);
    try {
      await api.get('/sanctum/csrf-cookie');
      const response = await api.post('/api/v1/login', values);
      const { user } = response.data;
      
      const activeWarehouse = user.warehouse_id || localStorage.getItem('selected_warehouse_id') || 1;
      setAuth(user, activeWarehouse);
      
      message.success('Authorization Successful');
      navigate('/'); 
    } catch (error: any) {
      const errorMsg = error.response?.status === 419 
        ? 'Security token expired. Please refresh.'
        : error.response?.data?.message || 'Authentication failed.';
      message.error(errorMsg);
    } finally {
      setLoading(false);
    }
  };

  return (
    // Fixed Height and Overflow Hidden to prevent scrolling
    <div className="flex h-screen w-screen overflow-hidden bg-white font-sans">
      
      {/* Left Side: Oxford Blue Brand Identity */}
      <div className="hidden lg:flex lg:w-1/2 relative bg-[#002147] h-full overflow-hidden">
        <img 
          src={loginHero} 
          alt="GRIDlock Security" 
          className="absolute inset-0 w-full h-full object-cover opacity-20"
        />
        <div className="relative z-10 flex flex-col justify-center p-16 text-white h-full">
          <Space size="middle" className="mb-6">
            <div className="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
              <Text style={{ color: '#002147', fontWeight: 900, fontSize: '28px' }}>G</Text>
            </div>
            <Title level={1} style={{ color: '#fff', margin: 0, fontWeight: 900, letterSpacing: '-2px' }}>
              GRIDlock ERP
            </Title>
          </Space>
          <Title level={3} style={{ color: 'rgba(255,255,255,0.8)', fontWeight: 300, maxWidth: '420px', lineHeight: 1.4 }}>
            Enterprise Resource Planning with a <span className="font-bold text-white underline underline-offset-4 decoration-blue-400">Security-First</span> Architecture.
          </Title>
          <div className="mt-8 flex gap-3">
             <div className="px-4 py-2 rounded-full border border-white/20 bg-white/5 text-[9px] font-black tracking-widest uppercase">
                v2.0 Stable
             </div>
             <div className="px-4 py-2 rounded-full border border-white/20 bg-white/5 text-[9px] font-black tracking-widest uppercase">
                Encrypted Session
             </div>
          </div>
        </div>
      </div>

      {/* Right Side: Login Form */}
      <div className="w-full lg:w-1/2 flex items-center justify-center p-8 bg-[#f8fafc] h-full">
        <div className="w-full max-w-md bg-white p-8 lg:p-10 rounded-2xl shadow-2xl border border-slate-100">
          <div className="mb-8 text-center lg:text-left">
            <Title level={2} style={{ fontWeight: 900, color: '#002147', marginBottom: 0, letterSpacing: '-1.5px' }}>
              Secure Access
            </Title>
            <Text style={{ color: '#94a3b8', fontSize: '13px', fontWeight: 600 }}>
              Provide corporate credentials to proceed.
            </Text>
          </div>

          <Form 
            name="login" 
            onFinish={onFinish} 
            layout="vertical" 
            size="large"
            requiredMark={false}
          >
            <Form.Item 
              name="email" 
              label={<Text style={{ color: '#002147', fontSize: '10px', fontWeight: 900, letterSpacing: '1px' }}>CORPORATE EMAIL</Text>}
              rules={[{ required: true, message: 'Email required' }]}
              className="mb-4"
            >
              <Input 
                prefix={<MailOutlined style={{ color: '#94a3b8' }} />} 
                placeholder="admin@gridlock.com" 
                className="rounded-lg border-slate-200 h-11"
              />
            </Form.Item>

            <Form.Item 
              name="password" 
              label={<Text style={{ color: '#002147', fontSize: '10px', fontWeight: 900, letterSpacing: '1px' }}>PASSWORD</Text>}
              rules={[{ required: true, message: 'Password required' }]}
              className="mb-2"
            >
              <Input.Password 
                prefix={<LockOutlined style={{ color: '#94a3b8' }} />} 
                placeholder="••••••••" 
                className="rounded-lg border-slate-200 h-11"
              />
            </Form.Item>

            <div className="flex justify-end mb-6">
              <Link to="/forgot-password" size="small" className="text-[11px] font-bold text-[#002147] opacity-60 hover:opacity-100 transition-opacity">
                RESET CREDENTIALS?
              </Link>
            </div>

            <Form.Item className="mb-0">
              <Button 
                type="primary" 
                htmlType="submit" 
                block 
                loading={loading}
                style={{
                    backgroundColor: '#002147',
                    height: '48px',
                    borderRadius: '24px',
                    fontSize: '14px',
                    fontWeight: 800,
                    letterSpacing: '0.5px',
                    boxShadow: '0 8px 16px -4px rgba(0, 33, 71, 0.3)'
                }}
              >
                AUTHORIZE ACCESS
              </Button>
            </Form.Item>
          </Form>

          <div className="mt-10 text-center border-t border-slate-50 pt-6">
            <Text style={{ color: '#cbd5e1', fontSize: '9px', fontWeight: 800, letterSpacing: '3px' }} className="uppercase">
              GRIDlock Core Security Engine
            </Text>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;