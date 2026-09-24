<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notification\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $notificationService): void
    {
        if (! empty($this->user->fcm_token)) {
            $notificationService->sendToToken(
                $this->user->fcm_token,
                $this->title,
                $this->body,
                $this->data,
                $this->user
            );
        }
    }
}
