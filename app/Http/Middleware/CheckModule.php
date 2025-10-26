<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->module !== $module) {
            return response()->json([
                'message' => "Unauthorized — Anda tidak memiliki akses ke modul {$module}"
            ], 403);
        }

        return $next($request);
    }
}
