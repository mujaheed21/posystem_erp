<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FulfillmentService;
use Throwable;
use RuntimeException;

class FulfillmentController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        try {
            FulfillmentService::fulfill($request->token);
            return response()->json(['status' => 'approved'], 200);
        } catch (RuntimeException $e) {
            $status = match($e->getMessage()) {
                'token_used' => 409,
                'token_expired' => 410,
                default => 422,
            };
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            // This will now return the ACTUAL error message to your test 
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}