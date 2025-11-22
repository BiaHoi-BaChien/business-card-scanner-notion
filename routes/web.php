<?php

use App\Services\PropertyConfigService;
use Illuminate\Support\Facades\Route;

// ① ドキュメントルートが register_business_card/public を指す場合は / を使う
Route::get('/', function (PropertyConfigService $propertyConfigService) {
    return view('app', [
        'propertyConfig' => $propertyConfigService->load(base_path()),
    ]);
});

// ② 直接 /register_business_card/ 配下にアクセスした既存のリンク向けにリダイレクトを残す
Route::get('/register_business_card', function () {
    return redirect('/');
});

// ③ 旧エントリーポイント /business_card_to_notion へのアクセスもルートに誘導する
Route::get('/business_card_to_notion', function () {
    return redirect('/');
});
