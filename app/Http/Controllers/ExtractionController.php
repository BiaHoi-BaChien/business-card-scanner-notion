<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessCardRequest;
use App\Services\ContactExtractionService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class ExtractionController extends Controller
{
    public function __construct(private ContactExtractionService $extractionService)
    {
    }

    /** @throws GuzzleException */
    public function extract(BusinessCardRequest $request): JsonResponse
    {
        /** @var UploadedFile[] $files */
        $files = $request->file('images', []);

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
