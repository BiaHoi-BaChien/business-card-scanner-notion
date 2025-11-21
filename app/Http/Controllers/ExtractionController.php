<?php

namespace App\Http\Controllers;

use App\Services\ContactExtractionService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ExtractionController extends Controller
{
    public function __construct(private ContactExtractionService $extractionService)
    {
    }

    /** @throws GuzzleException */
    public function extract(Request $request): JsonResponse
    {
        /** @var UploadedFile[] $files */
        $files = $request->file('images', []);
        if (empty($files)) {
            return response()->json(['error' => 'Upload 1-2 images via multipart form-data field "images[]"'], 400);
        }

        $count = count($files);
        if ($count < 1 || $count > 2) {
            return response()->json(['error' => 'Exactly 1 or 2 images are required'], 400);
        }

        $normalized = array_map(function (UploadedFile $file) {
            return [
                'tmp_name' => $file->getRealPath(),
                'type' => $file->getClientMimeType(),
            ];
        }, $files);

        $client = $this->extractionService->createClient($this->settings());
        $contact = $this->extractionService->extractContactData($client, $normalized);

        return response()->json(['contact' => $contact]);
    }
}
