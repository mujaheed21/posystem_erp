<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FulfillmentService;
use Throwable;
use RuntimeException;

class FulfillmentController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            FulfillmentService::fulfill($request->token);

            // Domain lifecycle result
            return response()->json([
                'status' => 'success',
                'state'  => 'reconciled',
            ], 200);

        } catch (RuntimeException $e) {

            $httpStatus = match ($e->getMessage()) {
                'token_used'    => 409,
                'token_expired' => 410,
                default         => 422,
            };

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $httpStatus);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'internal_error',
            ], 500);
        }
    }
}
