<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    public function show(): JsonResponse
    {
        $version = (string) (config('version.build', 'dev') ?? 'dev');

        return response()->json([
            'build_version' => $version,
        ]);
    }
}
