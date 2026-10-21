<?php

use App\Events\SupportMessageSent;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketUpdated;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('dispatches SupportTicketCreated event when a ticket is created', function () {
    Event::fake([SupportTicketCreated::class]);

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/customer/support/tickets', [
        'subject' => 'مشكلة في الطلب',
        'category' => 'delivery',
        'priority' => 'high',
        'message' => 'لم يصل طلبي بعد',
    ]);

    $response->assertSuccessful();

    Event::assertDispatched(SupportTicketCreated::class, function ($event) {
        return $event->ticket->subject === 'مشكلة في الطلب';
    });
});

test('dispatches SupportMessageSent event when customer sends a message', function () {
    Event::fake([SupportMessageSent::class]);

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $ticket = SupportTicket::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'subject' => 'مشكلة في التوصيل',
        'category' => 'delivery',
        'priority' => 'normal',
        'status' => 'open',
    ]);

    $response = $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/messages", [
        'message' => 'رسالة جديدة من العميل',
    ]);

    $response->assertSuccessful();

    Event::assertDispatched(SupportMessageSent::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id && $event->message->message === 'رسالة جديدة من العميل';
    });
});

test('dispatches SupportMessageSent event when admin replies to a ticket', function () {
    Event::fake([SupportMessageSent::class]);

    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $ticket = SupportTicket::create([
        'name' => 'عميل تجريبي',
        'email' => 'customer@test.com',
        'subject' => 'استفسار عام',
        'category' => 'general',
        'priority' => 'normal',
        'status' => 'open',
    ]);

    $response = $this->postJson("/api/v1/admin/support/tickets/{$ticket->id}/messages", [
        'message' => 'رد من مسؤول الدعم الفني',
    ]);

    $response->assertCreated();

    Event::assertDispatched(SupportMessageSent::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id && $event->message->message === 'رد من مسؤول الدعم الفني';
    });
});

test('dispatches SupportTicketUpdated event when ticket status is updated', function () {
    Event::fake([SupportTicketUpdated::class]);

    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $ticket = SupportTicket::create([
        'name' => 'عميل تجريبي',
        'email' => 'customer@test.com',
        'subject' => 'استفسار عام',
        'category' => 'general',
        'priority' => 'normal',
        'status' => 'open',
    ]);

    $response = $this->patchJson("/api/v1/admin/support/tickets/{$ticket->id}/status", [
        'status' => 'in_progress',
    ]);

    $response->assertSuccessful();

    Event::assertDispatched(SupportTicketUpdated::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id && $event->ticket->status === 'in_progress';
    });
});
