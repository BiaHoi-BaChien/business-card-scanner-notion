<?php

namespace App\Http\Controllers;

use App\Services\NotionService;
use App\Services\PropertyConfigService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NotionController extends Controller
{
    public function __construct(
        private NotionService $notionService,
        private PropertyConfigService $propertyConfigService
    ) {
    }

    /** @throws GuzzleException */
    public function verify(): JsonResponse
    {
        $client = $this->notionService->createClient($this->settings());
        $ok = $this->notionService->verifyConnection($client, $this->settings()['notion_data_source_id'] ?? '');

        return response()->json(['ok' => $ok]);
    }

    /** @throws GuzzleException */
    public function create(Request $request): JsonResponse
    {
        $body = $request->validate([
            'contact' => 'array',
            'attachments' => 'array',
        ]);

        $contact = is_array($body['contact'] ?? null) ? $body['contact'] : [];
        $attachments = is_array($body['attachments'] ?? null) ? $body['attachments'] : [];

        $properties = $this->propertyConfigService->load(base_path());
        $payload = $this->notionService->buildPayload($contact, $properties);
        $client = $this->notionService->createClient($this->settings());
        try {
            $page = $this->notionService->createPage($client, $payload, $attachments);
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 502);
        }

        return response()->json(['page' => $page]);
    }
}
