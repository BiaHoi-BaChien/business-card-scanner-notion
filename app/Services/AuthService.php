<?php

namespace App\Services;

class AuthService
{
    public function verifyPasswordLogin(string $username, string $password, array $settings): bool
    {
        $expectedUser = $this->decryptValue($settings['auth_username_enc'] ?? null, $settings['auth_secret'] ?? null);
        $expectedPass = $this->decryptValue($settings['auth_password_enc'] ?? null, $settings['auth_secret'] ?? null);

        return $expectedUser !== null && $expectedPass !== null
            && hash_equals($expectedUser, $username)
            && hash_equals($expectedPass, $password);
    }

    public function hashPasskey(string $passkey, string $secret): string
    {
        return hash('sha256', $passkey . $secret);
    }

    public function verifyPasskey(string $passkey, array $settings, ?string $registeredHash): bool
    {
        if (!$passkey || !$registeredHash) {
            return false;
        }

        $secret = $settings['auth_secret'] ?? null;
        if (!$secret) {
            return false;
        }

        return hash_equals($registeredHash, $this->hashPasskey($passkey, $secret));
    }

    private function decryptValue(?string $token, ?string $secret): ?string
    {
        if (!$token || !$secret) {
            return null;
        }

        try {
            $cipher = base64_decode($token, true);
            if ($cipher === false) {
                return null;
            }

            return $this->xorBytes($cipher, $this->deriveKey($secret));
        } catch (\Throwable) {
            return null;
        }
    }

    private function deriveKey(string $secret): string
    {
        return hash('sha256', $secret, true);
    }

    private function xorBytes(string $data, string $key): string
    {
        $result = '';
        $len = strlen($data);
        $keyLen = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $result .= $data[$i] ^ $key[$i % $keyLen];
        }

        return $result;
    }
}
