<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasskeyController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $body = $request->validate([
            'passkey' => 'required|string',
        ]);

        $settings = $this->settings();
        if (empty($settings['auth_secret'])) {
            return response()->json(['ok' => false, 'error' => 'Missing passkey or secret'], 400);
        }

        $request->session()->put('registered_passkey_hash', $this->authService->hashPasskey($body['passkey'], $settings['auth_secret']));

        return response()->json(['ok' => true]);
    }

    public function login(Request $request): JsonResponse
    {
        $body = $request->validate([
            'passkey' => 'required|string',
        ]);

        $settings = $this->settings();
        $stored = $request->session()->get('registered_passkey_hash');
        if ($this->authService->verifyPasskey($body['passkey'], $settings, $stored)) {
            $request->session()->put('auth', true);
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => 'Invalid passkey'], 401);
    }
}
