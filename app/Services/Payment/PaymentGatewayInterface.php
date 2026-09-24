<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate payment for the order.
     *
     * @return array{success: bool, payment_url: string, transaction_id: string, reference: string, client_secret?: ?string}
     */
    public function initiatePayment(Order $order, array $customerData = []): array;

    /**
     * Verify the authenticity of a webhook request.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Process a verified webhook request.
     *
     * @return array{order_id: string, status: string, transaction_id: string, raw_data: array}
     */
    public function processWebhook(Request $request): array;
}
