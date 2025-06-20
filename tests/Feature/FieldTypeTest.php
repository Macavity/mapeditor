<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FieldType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_field_types(): void
    {
        $user = User::factory()->create();
        $fieldType = FieldType::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/field-types');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'color', 'created_at', 'updated_at']
                ]
            ])
            ->assertJsonCount(3, 'data'); // 2 default + 1 factory
    }

    public function test_can_create_field_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/field-types', [
                'name' => 'Test Field Type',
                'color' => '#FF0000',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'color', 'created_at', 'updated_at']
            ]);

        $this->assertDatabaseHas('field_types', [
            'name' => 'Test Field Type',
            'color' => '#FF0000',
        ]);
    }

    public function test_can_update_field_type(): void
    {
        $user = User::factory()->create();
        $fieldType = FieldType::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/field-types/{$fieldType->id}", [
                'name' => 'Updated Field Type',
                'color' => '#00FF00',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Updated Field Type',
                    'color' => '#00FF00',
                ]
            ]);
    }

    public function test_can_delete_field_type(): void
    {
        $user = User::factory()->create();
        $fieldType = FieldType::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/field-types/{$fieldType->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('field_types', ['id' => $fieldType->id]);
    }

    public function test_default_field_types_are_created(): void
    {
        $this->assertDatabaseHas('field_types', [
            'name' => 'Default',
            'color' => '#00FF00',
        ]);

        $this->assertDatabaseHas('field_types', [
            'name' => 'No Entry',
            'color' => '#FF0000',
        ]);
    }
}
