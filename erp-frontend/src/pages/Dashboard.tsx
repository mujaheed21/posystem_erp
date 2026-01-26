import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card, Col, Row, Spin, Alert, Space, Typography } from 'antd';
import { 
  ArrowUpOutlined, 
  StockOutlined, 
  TransactionOutlined,
  SafetyCertificateOutlined,
  HistoryOutlined,
  BarChartOutlined
} from '@ant-design/icons';
import api from '../lib/axios';

const { Text } = Typography;

const Dashboard: React.FC = () => {
  const { data: stats, isLoading, error } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const [profitRes, stockRes, alertRes] = await Promise.all([
        api.get('/api/v1/reports/profit-loss'),
        api.get('/api/v1/inventory/valuation'),
        api.get('/api/v1/inventory/alerts')
      ]);

      const financialData = profitRes.data?.data || {};
      const valuationData = stockRes.data?.data || {};
      
      const locationTotal = Array.isArray(valuationData.by_location) 
        ? valuationData.by_location.reduce((sum: number, loc: any) => sum + (parseFloat(loc.total_value) || 0), 0)
        : 0;
      
      const transitTotal = parseFloat(valuationData.in_transit?.transit_value) || 0;
      const totalStockValue = locationTotal + transitTotal;

      return {
        profit: financialData.net_profit || 0,
        sales: financialData.total_sales || 0,
        inventoryValue: totalStockValue,
        lowStock: alertRes.data?.length || 0,
      };
    },
  });

  const formatFull = (val: number) => {
    return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  if (isLoading) return (
    <div className="h-64 flex items-center justify-center bg-white/50 rounded-xl">
      <Spin size="large" tip="Loading Financial Data..." />
    </div>
  );

  if (error) return (
    <div className="p-6">
      <Alert message="Ledger Connection Failed" type="error" showIcon />
    </div>
  );

  return (
    <div className="animate-in fade-in duration-500">
      {/* KPI Cards Row */}
      <Row gutter={[16, 16]}>
        {[
          { title: 'NET PROFIT', val: stats?.profit, color: '#10b981', icon: <ArrowUpOutlined />, bg: 'bg-green-50' },
          { title: 'TOTAL SALES', val: stats?.sales, color: '#0f172a', icon: <TransactionOutlined />, bg: 'bg-blue-50' },
          { title: 'STOCK VALUE', val: stats?.inventoryValue, color: '#0f172a', icon: <StockOutlined />, bg: 'bg-orange-50' }
        ].map((item, idx) => (
          <Col xs={24} sm={12} lg={6} key={idx}>
            <Card bordered={false} className="shadow-sm rounded-xl overflow-hidden h-28 flex items-center">
              <div className="flex justify-between items-center w-full">
                <div className="flex flex-col">
                  <Text type="secondary" className="text-[10px] font-bold tracking-wider uppercase">
                    {item.title}
                  </Text>
                  <Text className="text-xl font-bold truncate" style={{ color: item.color }}>
                    ₦{formatFull(item.val || 0)}
                  </Text>
                </div>
                <div className={`${item.bg} p-2.5 rounded-lg ml-3 flex-shrink-0 flex items-center justify-center text-lg`} style={{ color: item.color }}>
                  {item.icon}
                </div>
              </div>
            </Card>
          </Col>
        ))}

        <Col xs={24} sm={12} lg={6}>
          <Card bordered={false} className="shadow-sm rounded-xl h-28 flex items-center">
            <div className="flex justify-between items-center w-full">
              <div className="flex flex-col">
                <Text type="secondary" className="text-[10px] font-bold tracking-wider uppercase">
                  STOCK ALERTS
                </Text>
                <Text className="text-xl font-bold" style={{ color: (stats?.lowStock ?? 0) > 0 ? '#ef4444' : '#0f172a' }}>
                  {stats?.lowStock} ITEMS
                </Text>
              </div>
              <div className={(stats?.lowStock ?? 0) > 0 ? "bg-red-50 p-2.5 rounded-lg" : "bg-slate-50 p-2.5 rounded-lg"}>
                <SafetyCertificateOutlined className={(stats?.lowStock ?? 0) > 0 ? "text-red-500" : "text-slate-400"} />
              </div>
            </div>
          </Card>
        </Col>
      </Row>

      {/* Analysis Area */}
      <Row gutter={[24, 24]} className="mt-8">
        <Col xs={24} lg={16}>
          <Card 
            className="rounded-xl shadow-sm border-none" 
            title={<Text strong className="text-slate-700">Revenue Analysis</Text>}
          >
            <div className="h-[300px] flex flex-col items-center justify-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-200">
               <BarChartOutlined className="text-3xl text-slate-300 mb-2" />
               <Text type="secondary">Revenue data visualization will appear here</Text>
            </div>
          </Card>
        </Col>
        <Col xs={24} lg={8}>
          <Card 
            className="rounded-xl shadow-sm border-none" 
            title={<Text strong className="text-slate-700">Recent Activity</Text>}
          >
            <div className="h-[300px] flex items-center justify-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 text-slate-400 italic px-4 text-center">
               No activities logged for the current session.
            </div>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;