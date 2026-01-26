import React, { useState } from 'react';
import BarcodeScannerComponent from 'react-qr-barcode-scanner';
import { submitFulfillmentScan } from '../../lib/api/fulfillment';

export default function FulfillmentScanner() {
    const [status, setStatus] = useState<'idle' | 'scanning' | 'verifying' | 'success' | 'error'>('idle');
    const [errorMessage, setErrorMessage] = useState<string>('');

    const handleScan = async (err: any, result: any) => {
        if (result && status === 'scanning') {
            setStatus('verifying');
            const token = result.getText();

            const response = await submitFulfillmentScan(token);

            if (response.success) {
                setStatus('success');
            } else {
                setStatus('error');
                setErrorMessage(response.message);
            }
        }
    };

    return (
        <div className="p-4">
            <div className="w-full max-w-md mx-auto bg-black rounded-lg overflow-hidden relative aspect-square">
                {status === 'scanning' && (
                    <BarcodeScannerComponent
                        width="100%"
                        height="100%"
                        onUpdate={handleScan}
                    />
                )}

                {status === 'verifying' && (
                    <div className="absolute inset-0 flex items-center justify-center bg-blue-600/80 text-white">
                        Checking Ledger...
                    </div>
                )}

                {status === 'success' && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center bg-green-600 text-white">
                        <span className="text-4xl">✅</span>
                        <p>Reconciled</p>
                        <button onClick={() => setStatus('scanning')} className="mt-4 bg-white text-green-600 px-4 py-1 rounded">Next</button>
                    </div>
                )}

                {status === 'error' && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center bg-red-600 text-white">
                        <span className="text-4xl">❌</span>
                        <p>{errorMessage}</p>
                        <button onClick={() => setStatus('scanning')} className="mt-4 bg-white text-red-600 px-4 py-1 rounded">Retry</button>
                    </div>
                )}

                {status === 'idle' && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <button onClick={() => setStatus('scanning')} className="bg-blue-600 text-white px-6 py-2 rounded">Start Scanner</button>
                    </div>
                )}
            </div>
        </div>
    );
}