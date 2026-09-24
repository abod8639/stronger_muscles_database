<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Initiate payment for an order.
     */
    public function initiatePayment(InitiatePaymentRequest $request, string $orderId): JsonResponse
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        $gateway = $request->input('gateway', 'paymob');
        $customerData = [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
        ];

        try {
            $paymentData = $this->paymentService->initiateOrderPayment($order, $gateway, $customerData);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment initiated successfully',
                'data' => $paymentData,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
