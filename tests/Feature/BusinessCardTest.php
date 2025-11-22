<?php

namespace Tests\Feature;

use App\Services\NotionService;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class BusinessCardTest extends TestCase
{
    public function test_requires_name_to_create_business_card(): void
    {
        $response = $this->withSession(['auth' => true])->postJson('/api/notion/create', [
            'contact' => [
                'email' => 'user@example.com',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact.name']);
    }

    public function test_requires_email_to_create_business_card(): void
    {
        $response = $this->withSession(['auth' => true])->postJson('/api/notion/create', [
            'contact' => [
                'name' => 'Taro Yamada',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact.email']);
    }

    public function test_rejects_non_image_file_when_extracting_business_card(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withSession(['auth' => true])->post('/api/extract', [
            'images' => [$file],
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Only image files are allowed']);
    }

    public function test_creates_notion_page_successfully(): void
    {
        $page = ['id' => 'page-id', 'url' => 'https://www.notion.so/page-id'];
        $mockPayload = ['mock' => 'payload'];

        $original = env('NOTION_DATA_SOURCE_ID');
        putenv('NOTION_DATA_SOURCE_ID=test-data-source-id');
        $_ENV['NOTION_DATA_SOURCE_ID'] = 'test-data-source-id';

        $mockClient = Mockery::mock(Client::class);
        $mockService = Mockery::mock(NotionService::class);
        $mockService->shouldReceive('buildPayload')->once()->andReturn($mockPayload);
        $mockService->shouldReceive('createClient')->once()->andReturn($mockClient);
        $mockService->shouldReceive('createPage')->once()->with($mockClient, $mockPayload, [])->andReturn($page);

        $this->app->instance(NotionService::class, $mockService);

        try {
            $response = $this->withSession(['auth' => true])->postJson('/api/notion/create', [
                'contact' => [
                    'name' => 'Taro Yamada',
                    'email' => 'user@example.com',
                ],
            ]);

            $response->assertStatus(200)
                ->assertJson(['page' => $page]);
        } finally {
            if ($original === null) {
                putenv('NOTION_DATA_SOURCE_ID');
                unset($_ENV['NOTION_DATA_SOURCE_ID']);
            } else {
                putenv('NOTION_DATA_SOURCE_ID=' . $original);
                $_ENV['NOTION_DATA_SOURCE_ID'] = $original;
            }
        }
    }

    public function test_returns_error_when_notion_data_source_is_missing(): void
    {
        $original = env('NOTION_DATA_SOURCE_ID');
        putenv('NOTION_DATA_SOURCE_ID=');
        $_ENV['NOTION_DATA_SOURCE_ID'] = '';

        try {
            $response = $this->withSession(['auth' => true])->postJson('/api/notion/create', [
                'contact' => [
                    'name' => 'Taro Yamada',
                    'email' => 'user@example.com',
                ],
            ]);

            $response->assertStatus(500)
                ->assertJson(['error' => 'NOTION_DATA_SOURCE_ID is not configured']);
        } finally {
            if ($original === null) {
                putenv('NOTION_DATA_SOURCE_ID');
                unset($_ENV['NOTION_DATA_SOURCE_ID']);
            } else {
                putenv('NOTION_DATA_SOURCE_ID=' . $original);
                $_ENV['NOTION_DATA_SOURCE_ID'] = $original;
            }
        }
    }
}
