<?php

declare(strict_types=1);

use App\Models\User;

it('returns the authenticated user', function (): void {
    $user = User::factory()->create(['email' => 'jean.dupont@example.com']);

    $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

    $response->assertOk()->assertJsonPath('data.email', 'jean.dupont@example.com');
});

it('rejects an unauthenticated request', function (): void {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});
