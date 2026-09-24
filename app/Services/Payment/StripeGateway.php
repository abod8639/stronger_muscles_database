<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    protected ?string $secretKey;

    protected ?string $webhookSecret;

    protected string $currency;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->currency = config('services.stripe.currency', 'usd');
    }

    /**
     * Initiate payment for the order.
     */
    public function initiatePayment(Order $order, array $customerData = []): array
    {
        $amountCents = (int) round($order->total_amount * 100);

        if (empty($this->secretKey)) {
            $mockId = 'pi_sim_'.uniqid();

            return [
                'success' => true,
                'payment_url' => "https://checkout.stripe.com/pay/{$mockId}",
                'transaction_id' => $mockId,
                'reference' => (string) $order->id,
                'client_secret' => "{$mockId}_secret_test",
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post('https://api.stripe.com/v1/payment_intents', [
                    'amount' => $amountCents,
                    'currency' => $this->currency,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'user_id' => (string) $order->user_id,
                    ],
                    'description' => "Order #{$order->id} payment",
                ])->throw()->json();

            return [
                'success' => true,
                'payment_url' => $response['client_secret'] ?? '',
                'transaction_id' => $response['id'],
                'reference' => (string) $order->id,
                'client_secret' => $response['client_secret'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Stripe payment initiation failed', [
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
        $signature = $request->header('Stripe-Signature');

        if (empty($signature)) {
            return false;
        }

        if (empty($this->webhookSecret)) {
            return app()->environment('local', 'testing');
        }

        // Stripe signature verification
        $payload = $request->getContent();
        $sigParts = explode(',', $signature);
        $timestamp = null;
        $sigHash = null;

        foreach ($sigParts as $part) {
            $items = explode('=', trim($part), 2);
            if (count($items) === 2) {
                if ($items[0] === 't') {
                    $timestamp = $items[1];
                } elseif ($items[0] === 'v1') {
                    $sigHash = $items[1];
                }
            }
        }

        if (! $timestamp || ! $sigHash) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return hash_equals($expectedSignature, $sigHash);
    }

    /**
     * Process a verified webhook request.
     */
    public function processWebhook(Request $request): array
    {
        $payload = $request->all();
        $type = $payload['type'] ?? '';
        $dataObject = $payload['data']['object'] ?? [];

        $orderId = data_get($dataObject, 'metadata.order_id', '');
        $status = ($type === 'payment_intent.succeeded' || $type === 'checkout.session.completed') ? 'paid' : 'failed';
        $transactionId = $dataObject['id'] ?? '';

        return [
            'order_id' => (string) $orderId,
            'status' => $status,
            'transaction_id' => (string) $transactionId,
            'raw_data' => $payload,
        ];
    }
}
