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
}
