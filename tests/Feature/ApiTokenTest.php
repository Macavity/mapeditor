<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createTestUser()
{
    return User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
}

test('user can create api token', function () {
    $user = createTestUser();
    
    $response = $this->actingAs($user)
        ->postJson('/api/api-tokens', [
            'name' => 'Test Token',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'token',
        ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'Test Token',
    ]);
});

test('api token can access protected endpoints', function () {
    $user = createTestUser();
    
    // Create a token
    $token = $user->createToken('test-token')->plainTextToken;
    
    // Test accessing protected endpoint with token
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])->getJson('/api/tile-maps');
    
    $response->assertStatus(200);
});

test('unauthenticated requests are rejected', function () {
    $response = $this->getJson('/api/tile-maps');
    
    $response->assertStatus(401);
});

test('user can revoke api token', function () {
    $user = createTestUser();
    
    // Create a token
    $token = $user->createToken('test-token');
    
    // Revoke the token
    $response = $this->actingAs($user)
        ->deleteJson('/api/api-tokens/' . $token->accessToken->id);
        
    $response->assertStatus(200);
    
    // Verify the token was deleted
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});
