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
        $systemPrompt = 'あなたは名刺から構造化された連絡先情報を抽出するアシスタントです。 '
            . '常に name, company, website, email, phone_number_1, phone_number_2, industry のキーを持つ単一の JSON オブジェクトだけを返してください。 '
            . '欠けている値は空文字にします。複数画像に別の名刺が写っていても、すべての手がかりを 1 件のレコードに統合します。 '
            . '複数の電話番号がある場合は重複を除いた最大 2 件に絞り込み、メール・URL・会社名はより完全で新しいものを優先します。 '
            . '記載された Web サイトや信頼できる公開情報を基に業種・事業内容を調査し、会社名からだけで推測しないでください。 '
            . '業種は捏造や憶測を避け、判断材料がなければ "不明" とし、その旨を説明します。おおむね 100 文字で日本語の要約を記載し、値はすべて日本語で返します。 '
            . '氏名や会社名を翻訳・言い換えせず、印刷どおりの文字間や句読点を含めてそのまま記載してください。';

        $userMessage = array_merge([
            ['type' => 'text', 'text' => '提供されたすべての名刺画像から連絡先情報を抽出し、1 件のレコードとして統合してください。複数レコードは作成しないでください。'],
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
