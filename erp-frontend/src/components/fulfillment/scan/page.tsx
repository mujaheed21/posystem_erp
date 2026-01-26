import FulfillmentScanner from '@/components/fulfillment/FulfillmentScanner';

export default function ScanPage() {
    return (
        <div className="container mx-auto py-10">
            <div className="text-center mb-8">
                <h1 className="text-3xl font-extrabold text-slate-900">GRIDlock Fulfillment</h1>
                <p className="text-slate-500">Scan QR to reconcile inventory</p>
            </div>
            <FulfillmentScanner />
        </div>
    );
}