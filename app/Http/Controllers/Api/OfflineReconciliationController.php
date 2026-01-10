<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfflineFulfillmentPending;
use App\Services\OfflineReconciliationService;
use Illuminate\Http\Request;

class OfflineReconciliationController extends Controller
{
    public function __construct(
        protected OfflineReconciliationService $service
    ) {}

    public function approve(OfflineFulfillmentPending $pending)
    {
        $this->service->approve(
            $pending->id,
            auth()->user()
        );

        return response()->json(['state' => 'approved']);
    }

    public function reject(Request $request, OfflineFulfillmentPending $pending)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $this->service->reject(
            $pending->id,
            $request->reason,
            auth()->user()
        );

        return response()->json(['state' => 'rejected']);
    }

    public function reconcile(OfflineFulfillmentPending $pending)
    {
        $pending->refresh();
        $this->service->reconcile($pending->id);

        return response()->json(['state' => 'reconciled']);
    }
}
