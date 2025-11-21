<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

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

    /** @throws GuzzleException */
    public function verifyConnection(Client $client, string $dataSourceId): bool
    {
        try {
            $resp = $client->get('data_sources/' . $dataSourceId);

            return $resp->getStatusCode() === 200;
        } catch (GuzzleException) {
            return false;
        }
    }

    public function buildPayload(array $contact, array $properties): array
    {
        $company = isset($contact['company']) ? $this->sanitizeCompany($contact['company']) : '';

        return [
            'parent' => [
                'type' => 'data_source_id',
                'data_source_id' => env('NOTION_DATA_SOURCE_ID'),
            ],
            'properties' => [
                $properties['name'] => [
                    'title' => [[
                        'text' => ['content' => $contact['name'] ?? ''],
                    ]],
                ],
                $properties['company'] => [
                    'rich_text' => [[
                        'text' => ['content' => $company],
                    ]],
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

    /**
     * @throws GuzzleException
     */
    public function createPage(Client $client, array $payload, array $attachments): array
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

        $status = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);

        if ($status < 200 || $status >= 300) {
            $message = $body['error']['message'] ?? $body['message'] ?? 'Unexpected response from Notion API';

            throw new \RuntimeException("Failed to create Notion page (status {$status}): {$message}");
        }

        return $body;
    }

    private function sanitizeCompany(string $company): string
    {
        return str_replace(',', '、', $company);
    }
}
