<?php

use App\Models\Admin;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can retrieve paginated notifications list with statistics', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    Notification::create([
        'title' => 'عرض خاص',
        'body' => 'خصم 20% على جميع المكملات',
        'type' => 'promo',
        'target_type' => 'all',
    ]);

    $response = $this->getJson('/api/v1/admin/notifications');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'body',
                    'type',
                    'target_type',
                    'is_read',
                    'created_at',
                ],
            ],
            'stats' => [
                'total',
                'sent_today',
                'broadcast',
                'targeted',
            ],
        ]);

    expect($response->json('stats.total'))->toBe(1)
        ->and($response->json('stats.broadcast'))->toBe(1);
});

test('admin can send a broadcast notification to all users', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $response = $this->postJson('/api/v1/admin/notifications', [
        'title' => 'تحديث جديد',
        'body' => 'تم إضافة منتجات جديدة إلى المتجر',
        'type' => 'general',
        'target_type' => 'all',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.title', 'تحديث جديد');

    $this->assertDatabaseHas('notifications', [
        'title' => 'تحديث جديد',
        'target_type' => 'all',
    ]);
});

test('admin can send a targeted notification to a specific user', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/admin/notifications', [
        'title' => 'تنبيه شخصي',
        'body' => 'لديك طلب معلق بانتظار الدفع',
        'type' => 'order',
        'target_type' => 'user',
        'user_id' => $user->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.user_id', (string) $user->id);

    $this->assertDatabaseHas('notifications', [
        'title' => 'تنبيه شخصي',
        'user_id' => $user->id,
        'target_type' => 'user',
    ]);
});

test('admin can delete a notification', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $notification = Notification::create([
        'title' => 'إشعار للحذف',
        'body' => 'سيتم حذفه',
        'type' => 'general',
        'target_type' => 'all',
    ]);

    $response = $this->deleteJson("/api/v1/admin/notifications/{$notification->id}");

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});

test('customer can retrieve their notifications and unread count', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    // 1 broadcast
    Notification::create([
        'title' => 'إشعار عام',
        'body' => 'مرحباً بالجميع',
        'type' => 'general',
        'target_type' => 'all',
    ]);

    // 1 user targeted
    Notification::create([
        'title' => 'إشعار خاص',
        'body' => 'مرحباً بك',
        'type' => 'system',
        'target_type' => 'user',
        'user_id' => $user->id,
    ]);

    // 1 targeted to another user (should not appear)
    $otherUser = User::factory()->create();
    Notification::create([
        'title' => 'شخص آخر',
        'body' => 'ليس لك',
        'type' => 'system',
        'target_type' => 'user',
        'user_id' => $otherUser->id,
    ]);

    $response = $this->getJson('/api/v1/customer/notifications');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('unread_count', 2);

    $unreadResponse = $this->getJson('/api/v1/customer/notifications/unread-count');
    $unreadResponse->assertSuccessful()
        ->assertJsonPath('unread_count', 2);
});

test('customer can mark a notification as read and mark all as read', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $notif1 = Notification::create([
        'title' => 'إشعار 1',
        'body' => 'نص 1',
        'type' => 'general',
        'target_type' => 'all',
    ]);

    $notif2 = Notification::create([
        'title' => 'إشعار 2',
        'body' => 'نص 2',
        'type' => 'general',
        'target_type' => 'all',
    ]);

    // Mark single as read
    $response = $this->postJson("/api/v1/customer/notifications/{$notif1->id}/read");
    $response->assertSuccessful()
        ->assertJsonPath('unread_count', 1);

    $this->assertDatabaseHas('notification_reads', [
        'user_id' => $user->id,
        'notification_id' => $notif1->id,
    ]);

    // Mark all as read
    $markAllResponse = $this->postJson('/api/v1/customer/notifications/read-all');
    $markAllResponse->assertSuccessful()
        ->assertJsonPath('unread_count', 0);

    $this->assertDatabaseHas('notification_reads', [
        'user_id' => $user->id,
        'notification_id' => $notif2->id,
    ]);
});

test('guest cannot access notification endpoints', function () {
    $this->getJson('/api/v1/admin/notifications')->assertUnauthorized();
    $this->getJson('/api/v1/customer/notifications')->assertUnauthorized();
});
