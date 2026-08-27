<?php

declare(strict_types=1);

use App\Models\User;

it('logs in with valid credentials', function (): void {
    $user = User::factory()->create(['email' => 'jean.dupont@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jean.dupont@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()->assertJsonPath('data.email', 'jean.dupont@example.com');
    $this->assertAuthenticatedAs($user);
});

it('rejects an unknown email', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    $this->assertGuest();
});

it('rejects an incorrect password', function (): void {
    User::factory()->create(['email' => 'jean.dupont@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jean.dupont@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    $this->assertGuest();
});

it('throttles repeated login attempts', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
