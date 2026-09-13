<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $userRole = Role::where('name', 'user')->first();

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // auto-hash via cast 'hashed' di Model User
            'role_id' => $userRole?->id, // semua user baru otomatis dapat role "user"
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => $user->load('role'),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau password salah.'], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $user->load('role.permissions'),
            'token' => $token,
        ]);
    }

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me()
    {
        return response()->json(request()->user()->load('role.permissions'));
    }

    /**
     * Tahap 1: user submit email -> sistem cek terdaftar,
     * lalu generate kode verifikasi 6 digit, disimpan di tabel
     * bawaan Laravel `password_reset_tokens` (kolom `token` dipakai
     * untuk menyimpan kode, bukan token panjang seperti default).
     * (Untuk tugas kuliah: kode dikembalikan langsung di response.
     *  Di production seharusnya dikirim via email, bukan di-return.)
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => now()]
        );

        return response()->json([
            'message' => 'Kode verifikasi berhasil dibuat.',
            'dev_only_code' => $code, // TODO: hapus ini setelah integrasi email asli
        ]);
    }

    /**
     * Tahap 2: user submit email + kode + password baru.
     * Kode dianggap kedaluwarsa setelah 15 menit sejak dibuat.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (! $reset || now()->diffInMinutes($reset->created_at) > 15) {
            return response()->json(['message' => 'Kode verifikasi salah atau sudah kedaluwarsa.'], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => $request->password]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}