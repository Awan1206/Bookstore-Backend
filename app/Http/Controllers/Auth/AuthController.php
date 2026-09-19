<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Support\Carbon;
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
     * Step 1 — User submits email.
     * Generate a 6-digit OTP, store it in password_reset_tokens, send via email.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => $otp,
                'created_at' => now(),
            ]
        );

        $user->notify(new ResetPasswordOtp($otp));

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ]);
    }

    /**
     * Step 2 — User submits email + OTP code.
     * OTP expires after 15 minutes. On success, issue an opaque reset_token
     * and delete the OTP so it can't be reused.
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (! $record || now()->diffInMinutes(Carbon::parse($record->created_at)) > 15) {
            return response()->json(['message' => 'Kode OTP salah atau sudah kedaluwarsa.'], 422);
        }

        $resetToken = 'RESET_' . Str::random(64);

        DB::table('password_reset_tokens')->where('email', $request->email)->update([
            'token'      => $resetToken,
            'created_at' => now(),
        ]);

        return response()->json([
            'message'     => 'OTP valid. Silakan buat password baru.',
        ]);
    }

    /**
     * Step 3 — User submits reset_token + new password.
     * Token expires after 15 minutes.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', 'like', 'RESET_%')
            ->first();

        if (! $record || now()->diffInMinutes(Carbon::parse($record->created_at)) > 15) {
            return response()->json(['message' => 'Token reset tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => $request->password]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}