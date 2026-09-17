<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private channel per user untuk live chat: "chat.{userId}".
 * Cuma pemilik channel sendiri yang boleh "dengar" (listen) di channel ini.
 *
 * - User biasa: dengar balasan admin di chat.{user_id} miliknya sendiri.
 * - Admin: dengar pesan masuk dari user di chat.{admin_id} miliknya sendiri.
 *
 * Otorisasi ini hanya menentukan siapa yang boleh LISTEN di channel tsb,
 * bukan menentukan siapa lawan bicaranya (itu sudah diatur di ChatController).
 */
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});