<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Chat $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }

    /**
     * Broadcast ke private channel "chat.{userId}" milik si penerima pesan,
     * supaya cuma penerima yang bisa dengar event ini (lihat routes/channels.php).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->chat->receiver_id),
        ];
    }

    // Nama event yang didengar di frontend (Echo.private('chat.X').listen('.message.sent', ...))
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    // Data yang dikirim ke frontend saat event diterima
    public function broadcastWith(): array
    {
        return [
            'id' => $this->chat->id,
            'sender_id' => $this->chat->sender_id,
            'receiver_id' => $this->chat->receiver_id,
            'message' => $this->chat->message,
            'is_read' => $this->chat->is_read,
            'created_at' => $this->chat->created_at->toIso8601String(),
        ];
    }
}