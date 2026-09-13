<?php

namespace App\Http\Controllers;

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

        $userIds = Chat::where('receiver_id', $adminId)
            ->orWhere('sender_id', $adminId)
            ->get()
            ->flatMap(fn ($chat) => [$chat->sender_id, $chat->receiver_id])
            ->unique()
            ->reject(fn ($id) => $id === $adminId)
            ->values();

        $users = User::whereIn('id', $userIds)
            ->withCount(['chatsSent as unread_count' => function ($q) use ($adminId) {
                $q->where('receiver_id', $adminId)->where('is_read', false);
            }])
            ->get(['id', 'name', 'username']);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'receiver_id' => ['required_if:is_admin_sender,true', 'exists:users,id'],
        ]);

        if ($authUser->isAdmin()) {
            $receiverId = $request->receiver_id;
        } else {
            $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();
            $receiverId = $admin?->id;
        }

        $chat = Chat::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $receiverId,
            'message' => $data['message'],
        ]);

        return response()->json(['message' => 'Pesan terkirim.', 'data' => $chat], 201);
    }
}