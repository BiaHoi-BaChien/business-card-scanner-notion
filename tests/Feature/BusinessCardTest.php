<?php

namespace Tests\Feature;

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
