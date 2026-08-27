<?php

declare(strict_types=1);

use App\Models\User;

it('logs out an authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/auth/logout');

    $response->assertNoContent();

    // `assertGuest()` sans argument vérifie le guard par défaut, que le
    // middleware `auth:sanctum` a basculé sur `sanctum` (`Auth::shouldUse()`)
    // — un `RequestGuard` qui mémorise l'utilisateur résolu pour toute la
    // durée du test (même conteneur entre requêtes simulées) et ne reflète
    // donc jamais la déconnexion. Le guard `web`, lui, est réellement muté
    // par `LogoutAction::execute()` — c'est celui qu'il faut vérifier.
    $this->assertGuest('web');
});

it('rejects logout for a guest', function (): void {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});
