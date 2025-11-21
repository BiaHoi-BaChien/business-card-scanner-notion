<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ContactExtractionService
{
    public function createClient(array $settings): Client
    {
        $proxy = env('HTTP_PROXY')
            ?? env('HTTPS_PROXY')
            ?? env('ALL_PROXY')
            ?? env('http_proxy')
            ?? env('https_proxy');

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
    public function extractContactData(Client $client, array $files): array
    {
        $systemPrompt = 'You are an assistant that extracts structured contact details from business cards. '
            . 'Always return a single JSON object with these keys: name, company, website, email, phone_number_1, '
            . 'phone_number_2, industry. If a value is missing, use an empty string. '
            . 'Multiple images may contain different business cards—merge every clue across all images into one consolidated contact. '
            . 'If multiple phone numbers are found, keep at most two unique ones. Prefer the most complete/modern-looking email, URL, '
            . 'and company name when variations exist. '
            . 'Investigate the actual business domain and main activities using the printed website or well-established public information—never infer them from the company name alone. '
            . 'Never invent or guess a business description. If no reliable clue is available, set industry to "不明" and explain that the details could not be determined. '
            . 'Summarize the industry in Japanese within roughly 100 characters, avoiding overly terse labels. Use Japanese for all returned values, including the industry. '
            . 'When the card shows a name in Japanese, keep it as-is; if both Japanese and English names appear, choose the Japanese name. '
            . 'Do not translate or rewrite names or company names—copy them exactly as printed on the card, including spacing and punctuation.';

        $userMessage = array_merge([
            ['type' => 'text', 'text' => 'Extract and merge the contact information from all provided business card images into one consolidated record. Do not create multiple records.'],
        ], $this->buildImageParts($files));

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

    private function buildImageParts(array $files): array
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
}
