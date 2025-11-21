<?php

use App\Services\PropertyConfigService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PropertyConfigService $propertyConfigService) {
    return view('app', [
        'propertyConfig' => $propertyConfigService->load(base_path()),
    ]);
});
