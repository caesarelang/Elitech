<?php

namespace App\Http\Controllers\Auth\Produksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login untuk modul Produksi
     */
    public function loginProduksi(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])
                    ->where('module', 'production')
                    ->whereIn('role', ['staff', 'manager'])
                    ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah, atau Anda tidak memiliki akses ke modul Produksi'
            ], 401);
        }

        if ($user->module !== 'production') {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke modul Produksi'
            ], 403);
        }

        $abilities = ['module:production'];
        if ($user->role === 'manager') {
            $abilities[] = 'role:manager';
        } else {
            $abilities[] = 'role:staff';
        }

        $token = $user->createToken('production-auth-token', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'module' => $user->module
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    /**
     * Login untuk modul Produksi
     */
    public function loginProduction(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])
                    ->where('module', 'production')
                    ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah, atau Anda tidak memiliki akses ke modul Produksi'
            ], 401);
        }

        if ($user->module !== 'production') {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke modul Produksi'
            ], 403);
        }

        $token = $user->createToken('production-auth-token', ['module:production'])->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'module' => $user->module
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout dari semua perangkat berhasil'
        ]);
    }
}