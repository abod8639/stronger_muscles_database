<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobGateway implements PaymentGatewayInterface
{
    protected ?string $apiKey;
    protected ?string $integrationId;
    protected ?string $iframeId;
    protected ?string $hmacSecret;
    protected string $currency;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.paymob.api_key');
        $this->integrationId = config('services.paymob.integration_id');
        $this->iframeId = config('services.paymob.iframe_id');
        $this->hmacSecret = config('services.paymob.hmac_secret');
        $this->currency = config('services.paymob.currency', 'EGP');
        $this->baseUrl = config('services.paymob.base_url', 'https://accept.paymob.com/api');
    }

    /**
     * Initiate payment for the order.
     */
    public function initiatePayment(Order $order, array $customerData = []): array
    {
        $amountCents = (int) round($order->total_amount * 100);

        // If credentials are not configured, provide a mock checkout URL for testing/dev
        if (empty($this->apiKey) || empty($this->integrationId)) {
            $mockTransactionId = 'paymob_sim_'.uniqid();
            return [
                'success' => true,
                'payment_url' => "https://accept.paymob.com/api/acceptance/iframes/test?payment_token={$mockTransactionId}",
                'transaction_id' => $mockTransactionId,
                'reference' => (string) $order->id,
            ];
        }

        try {
            // Step 1: Authentication Request
            $authResponse = Http::post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ])->throw()->json();

            $authToken = $authResponse['token'];

            // Step 2: Order Registration
            $orderResponse = Http::post("{$this->baseUrl}/ecommerce/orders", [
                'auth_token' => $authToken,
                'delivery_needed' => 'false',
                'amount_cents' => $amountCents,
                'currency' => $this->currency,
                'merchant_order_id' => (string) $order->id,
                'items' => [],
            ])->throw()->json();

            $paymobOrderId = $orderResponse['id'];

            // Step 3: Payment Key Request
            $user = $order->user;
            $billingData = [
                'apartment' => 'NA',
                'email' => $user->email ?? 'customer@example.com',
                'floor' => 'NA',
                'first_name' => $customerData['first_name'] ?? ($user->name ?? 'Customer'),
                'street' => 'NA',
                'building' => 'NA',
                'phone_number' => $user->phone_number ?? '+201000000000',
                'shipping_method' => 'PKG',
                'postal_code' => 'NA',
                'city' => 'Cairo',
                'country' => 'EG',
                'last_name' => $customerData['last_name'] ?? 'Client',
                'state' => 'Cairo',
            ];

            $keyResponse = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
                'auth_token' => $authToken,
                'amount_cents' => $amountCents,
                'expiration' => 3600,
                'order_id' => $paymobOrderId,
                'billing_data' => $billingData,
                'currency' => $this->currency,
                'integration_id' => $this->integrationId,
            ])->throw()->json();

            $paymentToken = $keyResponse['token'];
            $iframeUrl = "{$this->baseUrl}/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";

            return [
                'success' => true,
                'payment_url' => $iframeUrl,
                'transaction_id' => (string) $paymobOrderId,
                'reference' => (string) $order->id,
            ];
        } catch (\Throwable $e) {
            Log::error('Paymob payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify the authenticity of a webhook request.
     */
    public function verifyWebhook(Request $request): bool
    {
        $hmac = $request->query('hmac') ?? $request->header('X-Paymob-HMAC');

        if (empty($hmac)) {
            return false;
        }

        if (empty($this->hmacSecret)) {
            // If HMAC secret is not configured, reject in production; accept in local testing if matches mock
            return app()->environment('local', 'testing');
        }

        $obj = $request->input('obj') ?? $request->all();

        $concatenated = 
            ($obj['amount_cents'] ?? '') .
            ($obj['created_at'] ?? '') .
            ($obj['currency'] ?? '') .
            ($obj['error_occured'] ?? '') .
            ($obj['has_parent_transaction'] ?? '') .
            ($obj['id'] ?? '') .
            ($obj['integration_id'] ?? '') .
            ($obj['is_3d_secure'] ?? '') .
            ($obj['is_auth'] ?? '') .
            ($obj['is_capture'] ?? '') .
            ($obj['is_refunded'] ?? '') .
            ($obj['is_standalone_payment'] ?? '') .
            ($obj['is_voided'] ?? '') .
            (data_get($obj, 'order.id', '')) .
            ($obj['owner'] ?? '') .
            ($obj['pending'] ?? '') .
            (data_get($obj, 'source_data.pan', '')) .
            (data_get($obj, 'source_data.sub_type', '')) .
            (data_get($obj, 'source_data.type', '')) .
            (($obj['success'] ?? false) ? 'true' : 'false');

        $calculatedHmac = hash_hmac('sha512', $concatenated, $this->hmacSecret);

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Process a verified webhook request.
     */
    public function processWebhook(Request $request): array
    {
        $obj = $request->input('obj') ?? $request->all();

        $merchantOrderId = data_get($obj, 'order.merchant_order_id') ?? data_get($obj, 'merchant_order_id');
        $isSuccess = ($obj['success'] ?? false) === true || ($obj['success'] ?? '') === 'true';
        $transactionId = (string) ($obj['id'] ?? '');

        return [
            'order_id' => (string) $merchantOrderId,
            'status' => $isSuccess ? 'paid' : 'failed',
            'transaction_id' => $transactionId,
            'raw_data' => $obj,
        ];
    }
}
