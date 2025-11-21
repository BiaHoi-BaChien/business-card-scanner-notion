<?php

namespace Tests\Feature;

use App\Http\Requests\BusinessCardRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BusinessCardRequestTest extends TestCase
{
    public function test_accepts_jpeg_images(): void
    {
        $request = new BusinessCardRequest();

        $file = UploadedFile::fake()->image('card.jpeg');

        $validator = Validator::make(
            ['images' => [$file]],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->passes());
    }
}
