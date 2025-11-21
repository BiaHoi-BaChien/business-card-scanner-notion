<?php

namespace App\Services;

class PropertyConfigService
{
    private const DEFAULT_PROPERTIES = [
        'name' => '名前',
        'company' => '会社名',
        'website' => '会社HP',
        'email' => 'メールアドレス',
        'phone_number_1' => '電話番号1',
        'phone_number_2' => '電話番号2',
        'industry' => '業種',
    ];

    public function load(string $rootPath): array
    {
        $path = $rootPath . '/property_config.json';
        if (!file_exists($path)) {
            return self::DEFAULT_PROPERTIES;
        }

        $json = json_decode(file_get_contents($path), true);
        if (!is_array($json)) {
            return self::DEFAULT_PROPERTIES;
        }

        return array_merge(self::DEFAULT_PROPERTIES, array_filter($json, fn($v) => is_string($v) && $v !== ''));
    }
}
