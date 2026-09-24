<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected ?string $serverKey;

    protected ?string $projectId;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
        $this->projectId = config('services.fcm.project_id');
    }

    /**
     * Send push notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            Log::info("User #{$user->id} has no FCM token registered. Skipping push notification.");

            return false;
        }

        if (config('queue.default') === 'sync' || app()->environment('testing')) {
            return $this->sendToToken($user->fcm_token, $title, $body, $data, $user);
        }

        \App\Jobs\SendPushNotificationJob::dispatch($user, $title, $body, $data);

        return true;
    }

    /**
     * Send push notification to a device token.
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = [], ?User $user = null): bool
    {
        Log::info('Push Notification dispatched:', [
            'token' => substr($fcmToken, 0, 10).'...',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        // If in testing or missing server key, log and return true
        if (app()->environment('testing') || empty($this->serverKey)) {
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => 'high',
            ]);

            $json = $response->json();

            // Handle invalid/expired token
            if (isset($json['results'][0]['error'])) {
                $error = $json['results'][0]['error'];
                if (in_array($error, ['NotRegistered', 'InvalidRegistration', 'MismatchSenderId']) && $user) {
                    $user->update(['fcm_token' => null]);
                    Log::warning("FCM token invalidated and removed for user #{$user->id}: {$error}");
                }

                return false;
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('FCM send failed: '.$e->getMessage(), [
                'token' => substr($fcmToken, 0, 10).'...',
            ]);

            return false;
        }
    }
}
