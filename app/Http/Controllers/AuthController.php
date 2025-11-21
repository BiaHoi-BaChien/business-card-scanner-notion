<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $body = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authService->verifyPasswordLogin($body['username'], $body['password'], $this->settings());
        if ($result['ok']) {
            $request->session()->put('auth', true);

            if ($request->expectsJson() || $request->isJson()) {
                return response()->json(['ok' => true]);
            }

            return redirect('/');
        }

        Log::warning('Login failed', [
            'username' => $body['username'],
            'reason' => $result['error'] ?? 'Unknown error',
        ]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
        }

        return redirect('/')
            ->withInput($request->only(['username']))
            ->with('error', 'ユーザー名またはパスワードが正しくありません。');
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => (bool) $request->session()->get('auth', false),
            'has_registered_passkey' => (bool) $request->session()->get('registered_passkey_hash'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('auth');
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
