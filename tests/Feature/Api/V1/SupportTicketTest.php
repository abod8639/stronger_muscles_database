<?php

use App\Models\Admin;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('customer can create a support ticket with an initial message', function () {
    $user = User::factory()->create([
        'name' => 'Ahmed Customer',
        'email' => 'ahmed@test.com',
        'phone_number' => '0551234567',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/customer/support/tickets', [
        'subject' => 'مشكلة في استلام الطلب',
        'category' => 'orders_shipping',
        'priority' => 'urgent',
        'message' => 'لم استلم الطلب رقم 1234 حتى الآن',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'ticket_number',
                'subject',
                'category',
                'priority',
                'status',
                'messages',
            ],
        ]);

    $this->assertDatabaseHas('support_tickets', [
        'user_id' => $user->id,
        'subject' => 'مشكلة في استلام الطلب',
        'category' => 'orders_shipping',
        'priority' => 'urgent',
        'status' => 'open',
    ]);

    $this->assertDatabaseHas('support_messages', [
        'sender_type' => 'user',
        'sender_id' => $user->id,
        'message' => 'لم استلم الطلب رقم 1234 حتى الآن',
    ]);
});

test('customer can list their own tickets', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    SupportTicket::create([
        'user_id' => $user1->id,
        'name' => $user1->name,
        'email' => $user1->email,
        'subject' => 'استفسار عن البروتين',
        'category' => 'supplements',
        'status' => 'open',
    ]);

    SupportTicket::create([
        'user_id' => $user2->id,
        'name' => $user2->name,
        'email' => $user2->email,
        'subject' => 'تذكرة مستخدم آخر',
        'category' => 'other',
        'status' => 'open',
    ]);

    Sanctum::actingAs($user1, ['*']);

    $response = $this->getJson('/api/v1/customer/support/tickets');

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success');

    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['subject'])->toBe('استفسار عن البروتين');
});

test('customer can view ticket details and reply', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'subject' => 'استفسار الشحن',
        'category' => 'orders_shipping',
        'status' => 'waiting_user',
    ]);

    $response = $this->getJson("/api/v1/customer/support/tickets/{$ticket->id}");
    $response->assertSuccessful()
        ->assertJsonPath('data.id', $ticket->id);

    $replyResponse = $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/messages", [
        'message' => 'هذا ردي على استفساركم شكراً لكم',
    ]);

    $replyResponse->assertCreated()
        ->assertJsonPath('data.message', 'هذا ردي على استفساركم شكراً لكم');

    $ticket->refresh();
    expect($ticket->status)->toBe('in_progress');
});

test('customer can close their own ticket', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'subject' => 'تم حل المشكلة',
        'category' => 'general',
        'status' => 'open',
    ]);

    $response = $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/close");

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'closed');

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'closed',
    ]);
});

test('admin can retrieve tickets list with statistics and filters', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    SupportTicket::create([
        'subject' => 'شكوى تأخير',
        'name' => 'Ali',
        'email' => 'ali@test.com',
        'category' => 'complaint',
        'priority' => 'urgent',
        'status' => 'open',
    ]);

    SupportTicket::create([
        'subject' => 'استفسار عام',
        'name' => 'Sara',
        'email' => 'sara@test.com',
        'category' => 'general',
        'priority' => 'normal',
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/admin/support/tickets');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'data',
            'stats' => [
                'total',
                'open',
                'in_progress',
                'resolved',
                'urgent',
            ],
        ]);

    expect($response->json('stats.total'))->toBe(2)
        ->and($response->json('stats.open'))->toBe(1)
        ->and($response->json('stats.urgent'))->toBe(1);

    // Test filter by status
    $filterResponse = $this->getJson('/api/v1/admin/support/tickets?status=open');
    $filterResponse->assertSuccessful();
    expect($filterResponse->json('data'))->toHaveCount(1)
        ->and($filterResponse->json('data.0.subject'))->toBe('شكوى تأخير');
});

test('admin can reply to a customer ticket and user is notified', function () {
    $admin = Admin::factory()->create(['name' => 'Agent Khaled']);
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $user = User::factory()->create(['fcm_token' => 'sample_fcm_token']);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'subject' => 'استفسار بخصوص الدفع',
        'category' => 'payment_refunds',
        'priority' => 'normal',
        'status' => 'open',
    ]);

    $response = $this->postJson("/api/v1/admin/support/tickets/{$ticket->id}/messages", [
        'message' => 'أهلاً بك، تم فحص عملية الدفع وتأكيد نجاحها.',
        'status' => 'waiting_user',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.sender_type', 'admin');

    $ticket->refresh();
    expect($ticket->status)->toBe('waiting_user');

    // Verify in-app notification created
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'support',
    ]);
});

test('admin can add an internal note hidden from customer view', function () {
    $admin = Admin::factory()->create(['name' => 'Manager']);
    $user = User::factory()->create();

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'subject' => 'شكوى منتج تالف',
        'category' => 'complaint',
        'priority' => 'high',
        'status' => 'open',
    ]);

    // Admin adds internal note
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $noteResponse = $this->postJson("/api/v1/admin/support/tickets/{$ticket->id}/messages", [
        'message' => 'ملاحظة داخلية: العميل طلب استبدال، تم التواصل مع المستودع.',
        'is_internal_note' => true,
    ]);

    $noteResponse->assertCreated()
        ->assertJsonPath('data.is_internal_note', true);

    // Customer views ticket details -> internal note should NOT be present
    Sanctum::actingAs($user, ['*']);
    $customerView = $this->getJson("/api/v1/customer/support/tickets/{$ticket->id}");
    $customerView->assertSuccessful();

    $messages = $customerView->json('data.messages');
    expect($messages)->toBeEmpty();

    // Admin views ticket details -> internal note IS present
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $adminView = $this->getJson("/api/v1/admin/support/tickets/{$ticket->id}");
    $adminView->assertSuccessful();

    $adminMessages = $adminView->json('data.messages');
    expect($adminMessages)->toHaveCount(1)
        ->and($adminMessages[0]['is_internal_note'])->toBeTrue();
});

test('admin can update ticket status, priority, and assign agent', function () {
    $admin = Admin::factory()->create();
    $agent = Admin::factory()->create(['role' => Admin::ROLE_CUSTOMER_SUPPORT]);
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $ticket = SupportTicket::create([
        'subject' => 'طلب مساعدة',
        'name' => 'Customer',
        'email' => 'c@test.com',
        'category' => 'general',
        'priority' => 'low',
        'status' => 'open',
    ]);

    // Update status
    $this->patchJson("/api/v1/admin/support/tickets/{$ticket->id}/status", [
        'status' => 'in_progress',
    ])->assertSuccessful()->assertJsonPath('data.status', 'in_progress');

    // Update priority
    $this->patchJson("/api/v1/admin/support/tickets/{$ticket->id}/priority", [
        'priority' => 'urgent',
    ])->assertSuccessful()->assertJsonPath('data.priority', 'urgent');

    // Assign agent
    $this->patchJson("/api/v1/admin/support/tickets/{$ticket->id}/assign", [
        'admin_id' => $agent->id,
    ])->assertSuccessful()->assertJsonPath('data.admin_id', $agent->id);
});
