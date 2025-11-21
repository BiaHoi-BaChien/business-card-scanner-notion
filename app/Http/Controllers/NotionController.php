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
    public function create(Request $request): JsonResponse
    {
        $settings = $this->settings();

        $body = $request->validate([
            'contact' => 'required|array',
            'contact.name' => 'required|string',
            'contact.company' => 'nullable|string',
            'contact.website' => 'nullable|url',
            'contact.email' => 'required|email',
            'contact.phone_number_1' => 'nullable|string',
            'contact.phone_number_2' => 'nullable|string',
            'contact.industry' => 'nullable|string',
            'attachments' => 'array',
            'attachments.*' => 'string',
        ]);

        $contact = $body['contact'];
        $attachments = $body['attachments'] ?? [];

        if (empty($settings['notion_data_source_id'])) {
            return response()->json(['error' => 'NOTION_DATA_SOURCE_ID is not configured'], 500);
        }

        $properties = $this->propertyConfigService->load(base_path());
        $payload = $this->notionService->buildPayload($contact, $properties, $settings['notion_data_source_id']);
        $client = $this->notionService->createClient($settings);
        try {
            $page = $this->notionService->createPage($client, $payload, $attachments);
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 502);
        }

        return response()->json(['page' => $page]);
    }
}
