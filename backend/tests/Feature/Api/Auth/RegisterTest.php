<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
});

it('registers a new user as company_owner', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jean Dupont',
        'email' => 'jean.dupont@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'jean.dupont@example.com')
        ->assertJsonPath('data.roles.0', RoleName::CompanyOwner->value);

    $user = User::query()->where('email', 'jean.dupont@example.com')->firstOrFail();
    expect($user->hasRole(RoleName::CompanyOwner->value))->toBeTrue();
});

it('never returns the password hash', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jean Dupont',
        'email' => 'jean.dupont@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertJsonMissingPath('data.password');
});

it('rejects registration with a mismatched password confirmation', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jean Dupont',
        'email' => 'jean.dupont@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'something-else',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

it('rejects registration with an already used email', function (): void {
    User::factory()->create(['email' => 'jean.dupont@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jean Dupont',
        'email' => 'jean.dupont@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('throttles repeated registration attempts', function (): void {
    // Des tentatives invalides (e-mail déjà pris) plutôt que des inscriptions
    // réussies : `RegisterAction` authentifie l'utilisateur via `Auth::login()`,
    // ce qui, dans un test Pest, fait persister l'utilisateur sur le guard
    // partagé d'une requête simulée à l'autre et fausse la clé de limitation
    // (`resolveRequestSignature()` bascule sur l'utilisateur dès qu'il y en
    // a un). Rester en échec de validation garde la clé sur l'IP.
    User::factory()->create(['email' => 'jean.dupont@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jean Dupont',
        'email' => 'jean.dupont.overflow@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertTooManyRequests();
});
