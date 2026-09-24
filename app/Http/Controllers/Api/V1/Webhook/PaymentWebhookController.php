<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle incoming payment gateway webhooks.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        Log::info("Incoming payment webhook for [{$gateway}]", [
            'query' => $request->query(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $result = $this->paymentService->handleWebhook($gateway, $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'data' => $result,
            ]);
        } catch (\SecurityException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook signature',
            ], 403);
        } catch (\Throwable $e) {
            Log::error("Payment webhook processing failed for [{$gateway}]: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }
}
