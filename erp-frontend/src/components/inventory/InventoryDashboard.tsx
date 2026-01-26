import React, { useEffect, useState } from 'react';
import { getInventoryDashboard } from '@/lib/api/inventory';

export default function InventoryDashboard() {
    const [data, setData] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getInventoryDashboard()
            .then(setData)
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="p-6 text-center animate-pulse text-slate-500">Synchronizing with Ledger...</div>;

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            {/* Valuation Card */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Stock Valuation</h3>
                <div className="mt-2 flex items-baseline">
                    <p className="text-3xl font-bold text-slate-900">
                        {data?.valuation?.total_value?.toLocaleString() || 0}
                    </p>
                    <span className="ml-2 text-slate-500">{data?.valuation?.currency || 'USD'}</span>
                </div>
                <p className="mt-4 text-xs text-slate-400 font-mono">ENFORCED FIFO LOGIC</p>
            </div>

            {/* Low Stock Alerts */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-red-100">
                <h3 className="text-sm font-semibold text-red-500 uppercase tracking-wider">Critical Alerts</h3>
                <div className="mt-4 space-y-3">
                    {data?.alerts?.length > 0 ? data.alerts.map((alert: any) => (
                        <div key={alert.id} className="flex justify-between items-center p-2 bg-red-50 rounded">
                            <span className="text-sm font-medium text-slate-700">{alert.name}</span>
                            <span className="text-xs font-bold bg-red-200 text-red-700 px-2 py-1 rounded">
                                Qty: {alert.quantity}
                            </span>
                        </div>
                    )) : (
                        <p className="text-sm text-slate-400 italic">No low stock alerts detected.</p>
                    )}
                </div>
            </div>
        </div>
    );
}