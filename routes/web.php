<?php

use App\Services\PropertyConfigService;
use Illuminate\Support\Facades\Route;

// ① /business_card_to_notion 用のルートを追加
Route::get('/business_card_to_notion', function (PropertyConfigService $propertyConfigService) {
    return view('app', [
        'propertyConfig' => $propertyConfigService->load(base_path()),
    ]);
});

// ② 直接 /register_business_card/public/ などでアクセスしたとき用に / も残したいならリダイレクトでも可
Route::get('/', function () {
    return redirect('/business_card_to_notion');
});
