<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class NotionService
{
    public function createClient(array $settings): Client
    {
        return new Client([
            'base_uri' => 'https://api.notion.com/v1/',
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['notion_api_key'],
                'Notion-Version' => $settings['notion_version'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function buildPayload(array $contact, array $properties, string $dataSourceId): array
    {
        if ($dataSourceId === '') {
            throw new RuntimeException('NOTION_DATA_SOURCE_ID is not configured');
        }

        $company = isset($contact['company']) ? $this->sanitizeCompany($contact['company']) : '';

        $propertyValues = [
            'name' => $contact['name'] ?? '',
            'company' => $company,
            'website' => $contact['website'] ?? null,
            'email' => $contact['email'] ?? null,
            'phone_number_1' => $contact['phone_number_1'] ?? null,
            'phone_number_2' => $contact['phone_number_2'] ?? null,
            'industry' => $contact['industry'] ?? null,
        ];

        $notionProperties = [];

        foreach ($properties as $key => $config) {
            if (!isset($config['name'], $config['type'])) {
                continue;
            }

            $notionProperties[$config['name']] = $this->buildPropertyValue(
                $config['type'],
                $propertyValues[$key] ?? null
            );
        }

        return [
            'parent' => [
                'type' => 'data_source_id',
                'data_source_id' => $dataSourceId,
            ],
            'properties' => $notionProperties,
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function createPage(Client $client, array $payload, array $attachments): array
    {
        if (!empty($attachments)) {
            $payload['children'] = array_map(function (string $attachmentUrl) {
                $scheme = parse_url($attachmentUrl, PHP_URL_SCHEME);

                if (!in_array($scheme, ['http', 'https'], true)) {
                    throw new RuntimeException('attachments must be public HTTP/HTTPS URLs');
                }

                return [
                    'object' => 'block',
                    'type' => 'image',
                    'image' => [
                        'type' => 'external',
                        'external' => ['url' => $attachmentUrl],
                    ],
                ];
            }, $attachments);
        }

        $response = $client->post('pages', ['body' => json_encode($payload)]);

        $status = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);

        if ($status < 200 || $status >= 300) {
            $message = $body['error']['message'] ?? $body['message'] ?? 'Unexpected response from Notion API';

            throw new \RuntimeException("Failed to create Notion page (status {$status}): {$message}");
        }

        return $body;
    }

    private function buildPropertyValue(string $type, $value): array
    {
        return match ($type) {
            'title' => [
                'title' => [[
                    'text' => ['content' => $value ?? ''],
                ]],
            ],
            'rich_text' => [
                'rich_text' => [[
                    'text' => ['content' => $value ?? ''],
                ]],
            ],
            'url' => ['url' => $value ?: null],
            'email' => ['email' => $value ?: null],
            'phone_number' => ['phone_number' => $value ?: null],
            'select' => [
                'select' => $value ? ['name' => $value] : null,
            ],
            default => [
                'rich_text' => [[
                    'text' => ['content' => (string) ($value ?? '')],
                ]],
            ],
        };
    }

    private function sanitizeCompany(string $company): string
    {
        return str_replace(',', '、', $company);
    }
}
