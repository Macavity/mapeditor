<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non-admin users cannot access user management', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/manage-users');

    $response->assertStatus(403);
});

test('admin users can access user management', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/manage-users');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('manage-users/ManageUsers'));
});

test('admin can create a new user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/manage-users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => false,
    ]);

    $response->assertRedirect('/manage-users');
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_admin' => false,
    ]);
});

test('admin can create an admin user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/manage-users', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => true,
    ]);

    $response->assertRedirect('/manage-users');
    $this->assertDatabaseHas('users', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'is_admin' => true,
    ]);
});

test('admin can edit a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($admin)->put("/manage-users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'is_admin' => true,
    ]);

    $response->assertRedirect('/manage-users');
    $response->assertSessionHasNoErrors();
    $user->refresh();
    dump($user->toArray());
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->is_admin)->toBeTrue();
})->skip();

test('admin can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete("/manage-users/{$user->id}");

    $response->assertRedirect('/manage-users');
    $response->assertSessionHasNoErrors();
    dump(User::find($user->id));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
})->skip();

test('admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->delete("/manage-users/{$admin->id}");

    $response->assertRedirect('/manage-users');
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin can toggle admin status', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($admin)->patch("/manage-users/{$user->id}/toggle-admin");

    $response->assertRedirect('/manage-users');
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_admin' => true,
    ]);
});

test('admin cannot toggle their own admin status', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->patch("/manage-users/{$admin->id}/toggle-admin");

    $response->assertRedirect('/manage-users');
    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'is_admin' => true,
    ]);
});

test('registration is allowed by default', function () {
    config(['app.allow_registration' => true]);

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration can be disabled', function () {
    config(['app.allow_registration' => false]);

    $response = $this->get('/register');

    $response->assertStatus(403);
});

test('registration post is blocked when disabled', function () {
    config(['app.allow_registration' => false]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(403);
});

test('user factory admin method works', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->is_admin)->toBeTrue();
    expect($admin->isAdmin())->toBeTrue();
});

test('regular user is not admin', function () {
    $user = User::factory()->create();

    expect($user->is_admin)->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
});
