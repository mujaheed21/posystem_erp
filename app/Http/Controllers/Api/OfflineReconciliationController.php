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
        $this->service->approve($pending->id, auth()->user());

        return response()->json(['status' => 'approved']);
    }

    public function reject(Request $request, OfflineFulfillmentPending $pending)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $this->service->reject(
            $pending->id,
            auth()->user(),
            $request->reason
        );

        return response()->json(['status' => 'rejected']);
    }

    public function reconcile(OfflineFulfillmentPending $pending)
    {
        $this->service->reconcile($pending->id, auth()->user());

        return response()->json(['status' => 'fulfilled']);
    }
}
