<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // List semua user terdaftar (create user TIDAK ada di sini, hanya lewat /register)
    public function index()
    {
        $users = User::with('role')->latest()->paginate(15);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user)
    {
        return response()->json($user->load('role'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', Rule::unique('users', 'phone')->ignore($user->id)],
            'role_id' => ['sometimes', 'exists:roles,id'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => $user->load('role'),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }
}