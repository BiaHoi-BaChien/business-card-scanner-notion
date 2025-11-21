<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $body = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $ok = $this->authService->verifyPasswordLogin($body['username'], $body['password'], $this->settings());
        if ($ok) {
            $request->session()->put('auth', true);
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => (bool) $request->session()->get('auth', false),
            'has_registered_passkey' => (bool) $request->session()->get('registered_passkey_hash'),
        ]);
    }
}
