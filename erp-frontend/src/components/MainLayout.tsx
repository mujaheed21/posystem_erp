import React, { useState } from 'react';
import { Layout, Menu, Button, theme, Typography, Modal, Avatar, Space, Divider, Tooltip } from 'antd';
import {
  DashboardOutlined,
  ShopOutlined,
  BarChartOutlined,
  MenuUnfoldOutlined,
  MenuFoldOutlined,
  UserOutlined,
  LogoutOutlined,
  ExclamationCircleOutlined,
  ScanOutlined,
  PlusCircleOutlined,
  InboxOutlined,
  DollarOutlined,
  CalendarOutlined,
  CheckCircleFilled,
  CalculatorOutlined,
  BellOutlined,
  PlusOutlined
} from '@ant-design/icons';
import { useNavigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/useAuthStore';

const { Header, Sider, Content } = Layout;
const { Text, Title } = Typography;
const { confirm } = Modal;

const MainLayout: React.FC = () => {
  const [collapsed, setCollapsed] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout } = useAuthStore();
  
  const {
    token: { colorPrimary },
  } = theme.useToken();

  const getPageTitle = () => {
    const path = location.pathname;
    if (path === '/') return 'Dashboard';
    if (path.includes('sales')) return 'Sales & Commercial';
    if (path.includes('fulfillment')) return 'Warehouse Logistics';
    if (path.includes('reports')) return 'Analytics & Reports';
    return 'GRIDlock ERP';
  };

  const handleLogout = () => {
    confirm({
      title: 'Confirm Logout',
      icon: <ExclamationCircleOutlined />,
      content: 'Are you sure you want to sign out?',
      okText: 'Logout',
      okType: 'danger',
      async onOk() {
        await logout();
        navigate('/login', { replace: true });
      },
    });
  };

  // MASTER PILL STYLE: Forces consistency across all header actions
  const pillStyle: React.CSSProperties = {
    background: 'rgba(255,255,255,0.1)',
    color: '#fff',
    border: '1px solid rgba(255,255,255,0.2)',
    borderRadius: '20px',
    height: '34px',
    display: 'flex',
    alignItems: 'center',
    fontWeight: 800,
    fontSize: '11px',
    boxShadow: 'none'
  };

  return (
    <Layout style={{ minHeight: '100vh' }}>
      <Sider 
        trigger={null} 
        collapsible 
        collapsed={collapsed} 
        width={280}
        theme="light" 
        style={{
          overflow: 'hidden',
          height: '100vh',
          position: 'fixed',
          left: 0,
          top: 0,
          bottom: 0,
          zIndex: 100,
          border: 'none',
        }}
      >
        <div style={{ 
          height: '72px', 
          backgroundColor: '#002147', 
          display: 'flex', 
          alignItems: 'center', 
          padding: '0 24px',
          marginRight: '-2px',
          position: 'relative',
          zIndex: 101
        }}>
          <Space size="middle">
            <div style={{ 
                width: '40px', 
                height: '40px', 
                background: '#fff', 
                borderRadius: '8px', 
                display: 'flex', 
                alignItems: 'center', 
                justifyContent: 'center'
            }}>
              <Text style={{ color: '#002147', fontWeight: 900, fontSize: '24px' }}>G</Text>
            </div>
            {!collapsed && (
              <Title level={4} style={{ 
                  color: '#fff', 
                  margin: 0, 
                  fontWeight: 900, 
                  fontSize: '20px', 
                  letterSpacing: '-0.8px'
              }}>
                GRIDlock ERP
              </Title>
            )}
          </Space>
        </div>

        <div style={{ 
            height: 'calc(100vh - 72px)', 
            borderRight: '1px solid #f0f0f0',
            paddingTop: '20px'
        }}>
          <Menu
            mode="inline"
            selectedKeys={[location.pathname]}
            onClick={({ key }) => navigate(key)}
            style={{ borderRight: 0 }}
            items={[
              { key: '/', icon: <DashboardOutlined />, label: 'Overview Dashboard' },
              {
                key: 'commercial',
                icon: <DollarOutlined />,
                label: 'Commercial',
                children: [
                  { key: '/sales/create', icon: <PlusCircleOutlined />, label: 'Create New Sale' },
                ]
              },
              {
                key: 'operations',
                icon: <InboxOutlined />,
                label: 'Warehouse Ops',
                children: [
                  { key: '/fulfillment/scan', icon: <ScanOutlined />, label: 'QR Fulfillment Scan' },
                  { key: '/locations', icon: <ShopOutlined />, label: 'Business Locations' },
                ]
              },
              { 
                key: '/reports', 
                icon: <BarChartOutlined />, 
                label: 'Analytics & Reports',
                children: [
                  { key: '/reports/profit-loss', label: 'Profit & Loss' },
                  { key: '/reports/stock', label: 'Inventory Report' },
                ]
              },
            ]}
          />
        </div>
      </Sider>

      <Layout style={{ marginLeft: collapsed ? 80 : 280, transition: 'all 0.2s' }}>
        
        <Header 
          style={{ 
            background: '#002147', 
            padding: '0 40px', 
            height: '72px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            position: 'sticky',
            top: 0,
            zIndex: 90,
            marginLeft: '-1px', 
            border: 'none',
          }}
        >
          <div className="flex items-center gap-4">
            <Button
              type="text"
              icon={collapsed ? <MenuUnfoldOutlined style={{color: '#fff'}} /> : <MenuFoldOutlined style={{color: '#fff'}} />}
              onClick={() => setCollapsed(!collapsed)}
              className="hover:bg-white/10"
            />
            <div style={{ ...pillStyle, padding: '0 16px', gap: '8px' }}>
              <CheckCircleFilled style={{ color: '#4ade80' }} />
              <Text style={{ color: '#fff', fontSize: '11px', fontWeight: 900 }}>SYSTEM LIVE</Text>
            </div>
          </div>

          <Space size="middle" align="center">
            {/* UTILITY PILLS */}
            <Space size={8}>
              <Button style={{ ...pillStyle, width: '38px', padding: 0, justifyContent: 'center' }} icon={<PlusOutlined />} />
              <Button style={{ ...pillStyle, width: '38px', padding: 0, justifyContent: 'center' }} icon={<CalculatorOutlined />} />
              <Button style={{ ...pillStyle, width: '38px', padding: 0, justifyContent: 'center' }} icon={<BellOutlined />} />
              
              <div style={{ ...pillStyle, padding: '0 16px' }}>
                <Text style={{ color: '#fff', fontSize: '11px', fontWeight: 700 }}>
                    {new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
                </Text>
              </div>
            </Space>

            <Divider type="vertical" style={{ backgroundColor: 'rgba(255,255,255,0.2)', height: '24px' }} />

            {/* PROFILE PILL (Updated) */}
            <Tooltip title={user?.warehouse_name || 'Main Unit'}>
                <div style={{ ...pillStyle, padding: '0 14px', gap: '8px', cursor: 'pointer' }}>
                    <UserOutlined style={{ fontSize: '12px' }} />
                    <Text style={{ color: '#fff', fontSize: '11px', fontWeight: 900 }}>
                        {user?.name?.toUpperCase() || 'ADMIN'}
                    </Text>
                </div>
            </Tooltip>

            {/* LOGOUT PILL */}
            <Button 
              onClick={handleLogout}
              icon={<LogoutOutlined style={{ fontSize: '12px' }} />}
              style={pillStyle}
              className="hover:bg-red-500/20 hover:border-red-500/40"
            >
              LOGOUT
            </Button>
          </Space>
        </Header>

        {/* HERO AREA */}
        <div style={{ backgroundColor: '#002147', padding: '8px 40px 60px 40px', marginLeft: '-1px' }}>
           <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <Text style={{ color: 'rgba(255,255,255,0.5)', fontWeight: 900, fontSize: '10px', letterSpacing: '1px' }}>
                  {user?.business_name?.toUpperCase() || 'GRIDLOCK'} / {getPageTitle().toUpperCase()}
                </Text>
                <Title level={2} style={{ color: '#fff', margin: 0, fontWeight: 900, fontSize: '32px', letterSpacing: '-1px' }}>
                  {getPageTitle()}
                </Title>
              </div>
              <Button 
                icon={<CalendarOutlined style={{color: '#fff'}} />} 
                style={{ 
                    backgroundColor: 'rgba(255,255,255,0.1)', 
                    border: '1px solid rgba(255,255,255,0.3)', 
                    color: '#fff', 
                    borderRadius: '8px',
                    fontWeight: 700,
                    height: '42px'
                }}
              >
                SNAPSHOT
              </Button>
           </div>
        </div>

        <Content style={{ padding: '0 40px', marginTop: '-35px', paddingBottom: '40px' }}>
          <div style={{ maxWidth: '1600px', margin: '0 auto' }}>
            <Outlet />
          </div>
        </Content>
      </Layout>
    </Layout>
  );
};

export default MainLayout;