<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('support.tickets', function ($user) {
    return true;
});

Broadcast::channel('support.ticket.{id}', function ($user, $id) {
    return true;
});
