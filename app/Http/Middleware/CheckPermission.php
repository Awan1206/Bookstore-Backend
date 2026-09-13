<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Contoh pakai di routes:
     * Route::middleware('permission:manage-books')->group(...)
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk aksi ini.',
            ], 403);
        }

        return $next($request);
    }
}