<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Notification\PushNotificationService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymobGateway;
use App\Services\Payment\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected PushNotificationService $notificationService
    ) {}

    /**
     * Resolve a payment gateway instance by name.
     */
    public function getGateway(string $gatewayName): PaymentGatewayInterface
    {
        return match (strtolower($gatewayName)) {
            'paymob' => app(PaymobGateway::class),
            'stripe' => app(StripeGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}"),
        };
    }

    /**
     * Initiate payment for an existing order.
     */
    public function initiateOrderPayment(Order $order, string $gatewayName = 'paymob', array $customerData = []): array
    {
        if ($order->payment_status === 'paid') {
            throw new \DomainException('Order has already been paid.');
        }

        $gateway = $this->getGateway($gatewayName);
        $result = $gateway->initiatePayment($order, $customerData);

        $order->update([
            'payment_method' => $gatewayName,
            'transaction_id' => $result['transaction_id'] ?? $order->transaction_id,
        ]);

        return $result;
    }

    /**
     * Process an incoming webhook for a specific gateway.
     */
    public function handleWebhook(string $gatewayName, Request $request): array
    {
        $gateway = $this->getGateway($gatewayName);

        if (! $gateway->verifyWebhook($request)) {
            Log::warning("Unauthorized webhook attempt for {$gatewayName}", [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);

            throw new \SecurityException("Invalid webhook signature for {$gatewayName}");
        }

        $processed = $gateway->processWebhook($request);

        if (empty($processed['order_id'])) {
            Log::warning("Webhook received without order ID for {$gatewayName}", $processed);

            return ['status' => 'ignored', 'message' => 'No order reference found'];
        }

        return DB::transaction(function () use ($processed, $gatewayName) {
            $order = Order::where('id', $processed['order_id'])->lockForUpdate()->first();

            if (! $order) {
                Log::error("Order not found during webhook processing: {$processed['order_id']}");
                return ['status' => 'not_found', 'message' => 'Order not found'];
            }

            // Prevent duplicate processing
            if ($order->payment_status === 'paid') {
                return ['status' => 'already_processed', 'order_id' => $order->id];
            }

            $paymentStatus = $processed['status'];
            $orderStatus = $paymentStatus === 'paid' ? 'processing' : $order->status;

            $order->update([
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'transaction_id' => $processed['transaction_id'] ?: $order->transaction_id,
                'payment_details' => [
                    'gateway' => $gatewayName,
                    'processed_at' => now()->toIso8601String(),
                    'raw' => $processed['raw_data'],
                ],
            ]);

            // Notify user on payment success
            if ($paymentStatus === 'paid' && $order->user) {
                $this->notificationService->sendToUser(
                    $order->user,
                    'تم تأكيد الدفع بنجاح!',
                    "تم استلام دفعة طلبك رقم #{$order->id} بنجاح، جاري تجهيز الطلب الآن.",
                    [
                        'type' => 'order_paid',
                        'order_id' => (string) $order->id,
                    ]
                );
            }

            return [
                'status' => 'success',
                'order_id' => (string) $order->id,
                'payment_status' => $paymentStatus,
            ];
        });
    }
}
