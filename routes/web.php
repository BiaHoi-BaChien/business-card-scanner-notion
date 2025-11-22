<?php

use App\Services\PropertyConfigService;
use Illuminate\Support\Facades\Route;

// ① /register_business_card 用のルートを追加
Route::get('/register_business_card', function (PropertyConfigService $propertyConfigService) {
    return view('app', [
        'propertyConfig' => $propertyConfigService->load(base_path()),
    ]);
});

// ② 直接 /register_business_card/public/ などでアクセスしたとき用に / も残したいならリダイレクトでも可
Route::get('/', function () {
    return redirect('/register_business_card');
});
