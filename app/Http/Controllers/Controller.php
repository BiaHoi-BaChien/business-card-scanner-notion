<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function settings(): array
    {
        return [
            'openai_api_key' => env('OPENAI_API_KEY'),
            'notion_api_key' => env('NOTION_API_KEY'),
            'notion_data_source_id' => env('NOTION_DATA_SOURCE_ID'),
            'notion_version' => env('NOTION_VERSION', '2025-09-03'),
            'auth_secret' => env('AUTH_SECRET'),
            'auth_username_enc' => env('AUTH_USERNAME_ENC'),
            'auth_password_enc' => env('AUTH_PASSWORD_ENC'),
        ];
    }
}
