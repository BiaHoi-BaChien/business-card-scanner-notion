<?php

namespace App\Services;

class PropertyConfigService
{
    private const DEFAULT_PROPERTIES = [
        'name' => ['name' => '名前', 'type' => 'title'],
        'company' => ['name' => '会社名', 'type' => 'rich_text'],
        'website' => ['name' => '会社HP', 'type' => 'url'],
        'email' => ['name' => 'メールアドレス', 'type' => 'email'],
        'phone_number_1' => ['name' => '電話番号1', 'type' => 'phone_number'],
        'phone_number_2' => ['name' => '電話番号2', 'type' => 'phone_number'],
        'industry' => ['name' => '業種', 'type' => 'select'],
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

        $properties = self::DEFAULT_PROPERTIES;

        foreach (self::DEFAULT_PROPERTIES as $key => $defaults) {
            if (!array_key_exists($key, $json)) {
                continue;
            }

            $value = $json[$key];

            if (is_string($value) && $value !== '') {
                $properties[$key]['name'] = $value;
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            if (isset($value['name']) && is_string($value['name']) && $value['name'] !== '') {
                $properties[$key]['name'] = $value['name'];
            }

            if (isset($value['type']) && $this->isSupportedType($value['type'])) {
                $properties[$key]['type'] = $value['type'];
            }
        }

        return $properties;
    }

    private function isSupportedType(string $type): bool
    {
        return in_array($type, ['title', 'rich_text', 'url', 'email', 'phone_number', 'select'], true);
    }
}
