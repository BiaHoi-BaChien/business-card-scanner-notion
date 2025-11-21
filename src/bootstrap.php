<?php

namespace CardScanner;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

const DEFAULT_PROPERTIES = [
    'name' => '名前',
    'company' => '会社名',
    'website' => '会社HP',
    'email' => 'メールアドレス',
    'phone_number_1' => '電話番号1',
    'phone_number_2' => '電話番号2',
    'industry' => '業種',
];

function loadEnv(string $root): void
{
    if (file_exists($root . '/.env')) {
        Dotenv::createImmutable($root)->safeLoad();
    }
}

function getenvStripped(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $default;
    }
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);
    return $trimmed === '' ? null : $trimmed;
}

function loadSettings(): array
{
    return [
        'openai_api_key' => getenvStripped('OPENAI_API_KEY'),
        'notion_api_key' => getenvStripped('NOTION_API_KEY'),
        'notion_data_source_id' => getenvStripped('NOTION_DATA_SOURCE_ID'),
        'notion_version' => getenvStripped('NOTION_VERSION', '2025-09-03'),
        'auth_secret' => getenvStripped('AUTH_SECRET'),
        'auth_username_enc' => getenvStripped('AUTH_USERNAME_ENC'),
        'auth_password_enc' => getenvStripped('AUTH_PASSWORD_ENC'),
    ];
}

function loadPropertyConfig(string $root): array
{
    $path = $root . '/property_config.json';
    if (!file_exists($path)) {
        return DEFAULT_PROPERTIES;
    }

    $json = json_decode(file_get_contents($path), true);
    if (!is_array($json)) {
        return DEFAULT_PROPERTIES;
    }

    return array_merge(DEFAULT_PROPERTIES, array_filter($json, fn($v) => is_string($v) && $v !== ''));
}

function deriveKey(string $secret): string
{
    return hash('sha256', $secret, true);
}

function xorBytes(string $data, string $key): string
{
    $result = '';
    $len = strlen($data);
    $keyLen = strlen($key);
    for ($i = 0; $i < $len; $i++) {
        $result .= $data[$i] ^ $key[$i % $keyLen];
    }
    return $result;
}

function decryptValue(?string $token, ?string $secret): ?string
{
    if (!$token || !$secret) {
        return null;
    }

    try {
        $cipher = base64_decode($token, true);
        if ($cipher === false) {
            return null;
        }
        $plain = xorBytes($cipher, deriveKey($secret));
        return $plain;
    } catch (\Throwable $e) {
        return null;
    }
}

function hashPasskey(string $passkey, string $secret): string
{
    return hash('sha256', $passkey . $secret);
}

function verifyPasswordLogin(string $username, string $password, array $settings): bool
{
    $expectedUser = decryptValue($settings['auth_username_enc'] ?? null, $settings['auth_secret'] ?? null);
    $expectedPass = decryptValue($settings['auth_password_enc'] ?? null, $settings['auth_secret'] ?? null);

    return $expectedUser !== null && $expectedPass !== null
        && hash_equals($expectedUser, $username)
        && hash_equals($expectedPass, $password);
}

function verifyPasskey(string $passkey, array $settings): bool
{
    if (!$passkey || empty($_SESSION['registered_passkey_hash'])) {
        return false;
    }
    $secret = $settings['auth_secret'] ?? null;
    if (!$secret) {
        return false;
    }
    return hash_equals($_SESSION['registered_passkey_hash'], hashPasskey($passkey, $secret));
}

function buildImageParts(array $files): array
{
    $parts = [];
    foreach ($files as $file) {
        $mime = $file['type'] ?: 'application/octet-stream';
        $data = base64_encode(file_get_contents($file['tmp_name']));
        $parts[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:' . $mime . ';base64,' . $data,
            ],
        ];
    }
    return $parts;
}

function openaiClient(array $settings): Client
{
    $proxy = getenvStripped('HTTP_PROXY')
        ?? getenvStripped('HTTPS_PROXY')
        ?? getenvStripped('ALL_PROXY')
        ?? getenvStripped('http_proxy')
        ?? getenvStripped('https_proxy');

    $options = [
        'base_uri' => 'https://api.openai.com/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . $settings['openai_api_key'],
            'Content-Type' => 'application/json',
        ],
        'proxy' => $proxy,
    ];

    return new Client($options);
}

/** @throws GuzzleException */
function extractContactData(Client $client, array $files): array
{
    $systemPrompt = 'You are an assistant that extracts structured contact details from business cards. '
        . 'Always return a single JSON object with these keys: name, company, website, email, phone_number_1, '
        . 'phone_number_2, industry. If a value is missing, use an empty string. '
        . 'Multiple images may contain different business cards—merge every clue across all images into one consolidated contact. '
        . 'If multiple phone numbers are found, keep at most two unique ones. Prefer the most complete/modern-looking email, URL, '
        . 'and company name when variations exist. '
        . 'Infer the industry from the company name when not explicitly shown. Summarize the industry in Japanese within roughly 100 characters, '
        . 'avoiding overly terse labels. Use Japanese for all returned values, including the industry. '
        . 'When the card shows a name in Japanese, keep it as-is; if both Japanese and English names appear, choose the Japanese name. '
        . 'Do not translate or rewrite names or company names—copy them exactly as printed on the card, including spacing and punctuation.';

    $userMessage = array_merge([
        ['type' => 'text', 'text' => 'Extract and merge the contact information from all provided business card images into one consolidated record. Do not create multiple records.'],
    ], buildImageParts($files));

    $payload = [
        'model' => 'gpt-4o-mini',
        'temperature' => 0,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ],
        'response_format' => ['type' => 'json_object'],
    ];

    $response = $client->post('chat/completions', ['body' => json_encode($payload)]);
    $json = json_decode($response->getBody()->getContents(), true);
    $content = $json['choices'][0]['message']['content'] ?? '{}';
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        return [];
    }
    return $parsed;
}

function sanitizeCompany(string $company): string
{
    return str_replace(',', '、', $company);
}

function notionClient(array $settings): Client
{
    return new Client([
        'base_uri' => 'https://api.notion.com/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . $settings['notion_api_key'],
            'Notion-Version' => $settings['notion_version'],
            'Content-Type' => 'application/json',
        ],
    ]);
}

/** @throws GuzzleException */
function verifyNotionConnection(Client $client, string $dataSourceId): bool
{
    $resp = $client->get('data_sources/' . $dataSourceId);
    return $resp->getStatusCode() === 200;
}

function buildNotionPayload(array $contact, array $properties): array
{
    $company = isset($contact['company']) ? sanitizeCompany($contact['company']) : '';

    return [
        'parent' => [
            'type' => 'data_source_id',
            'data_source_id' => getenvStripped('NOTION_DATA_SOURCE_ID'),
        ],
        'properties' => [
            $properties['name'] => [
                'title' => [[
                    'text' => ['content' => $contact['name'] ?? ''],
                ]],
            ],
            $properties['company'] => [
                'select' => $company !== '' ? ['name' => $company] : null,
            ],
            $properties['website'] => [
                'url' => $contact['website'] ?? null,
            ],
            $properties['email'] => [
                'email' => $contact['email'] ?? null,
            ],
            $properties['phone_number_1'] => [
                'phone_number' => $contact['phone_number_1'] ?? null,
            ],
            $properties['phone_number_2'] => [
                'phone_number' => $contact['phone_number_2'] ?? null,
            ],
            $properties['industry'] => [
                'select' => $contact['industry'] ? ['name' => $contact['industry']] : null,
            ],
        ],
    ];
}

/** @throws GuzzleException */
function createNotionPage(Client $client, array $payload, array $attachments): array
{
    if (!empty($attachments)) {
        $payload['children'] = array_map(function (string $dataUrl) {
            return [
                'object' => 'block',
                'type' => 'image',
                'image' => [
                    'type' => 'external',
                    'external' => ['url' => $dataUrl],
                ],
            ];
        }, $attachments);
    }

    $response = $client->post('pages', ['body' => json_encode($payload)]);
    return json_decode($response->getBody()->getContents(), true);
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
