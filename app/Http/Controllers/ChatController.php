<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * USER: GET /api/chat -> ambil semua percakapan dia dengan admin.
     * ADMIN: GET /api/chat?user_id=5 -> ambil percakapan dengan user tertentu.
     * Support ?since=<timestamp> untuk polling (hanya ambil pesan baru).
     */
    public function index(Request $request)
    {
        $authUser = $request->user();

        if ($authUser->isAdmin()) {
            if (! $request->user_id) {
                return response()->json(['message' => 'user_id wajib diisi untuk admin.'], 422);
            }
            $otherUserId = $request->user_id;
        } else {
            $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();
            $otherUserId = $admin?->id;
        }

        $messages = Chat::where(function ($q) use ($authUser, $otherUserId) {
                $q->where('sender_id', $authUser->id)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($authUser, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $authUser->id);
            })
            ->when($request->since, fn ($q) => $q->where('created_at', '>', $request->since))
            ->orderBy('created_at')
            ->get();

        // tandai pesan masuk sebagai sudah dibaca
        Chat::where('sender_id', $otherUserId)
            ->where('receiver_id', $authUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    // ADMIN saja: daftar user yang pernah chat, buat sidebar percakapan
    public function conversations(Request $request)
    {
        $adminId = $request->user()->id;

        // Single query: get distinct user IDs from DB, no PHP collection gymnastics
        $userIds = Chat::selectRaw('
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id', [$adminId])
            ->where('sender_id', $adminId)
            ->orWhere('receiver_id', $adminId)
            ->distinct()
            ->pluck('other_id');

        $users = User::whereIn('id', $userIds)
            ->withCount(['chatsSent as unread_count' => function ($q) use ($adminId) {
                $q->where('receiver_id', $adminId)->where('is_read', false);
            }])
            ->get(['id', 'name', 'username', 'unread_count']);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();

        $rules = [
            'message' => ['required', 'string', 'max:1000'],
        ];

        if ($authUser->isAdmin()) {
            $rules['receiver_id'] = ['required', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        if ($authUser->isAdmin()) {
            $receiverId = $data['receiver_id'];
        } else {
            $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();

            if (! $admin) {
                return response()->json(['message' => 'Admin tidak ditemukan.'], 500);
            }

            $receiverId = $admin->id;
        }

        $chat = Chat::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $receiverId,
            'message' => $data['message'],
        ]);

        // Kirim real-time ke penerima lewat WebSocket (Reverb).
        // ShouldBroadcast otomatis di-queue kalau QUEUE_CONNECTION bukan 'sync',
        // kalau masih 'sync' maka ini jalan langsung (blocking, tapi tetap terkirim).
        broadcast(new MessageSent($chat))->toOthers();

        return response()->json(['message' => 'Pesan terkirim.', 'data' => $chat], 201);
    }
}