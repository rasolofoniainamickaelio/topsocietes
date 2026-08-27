<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Data\RegisterData;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Un compte créé ici est toujours `company_owner` — jamais admis au
 * back-office Filament (`User::canAccessPanel()`, ADR 0002). C'est le
 * point d'entrée public : revendiquer une fiche, s'abonner.
 */
class RegisterAction
{
    public function execute(RegisterData $data): User
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        $user->assignRole(RoleName::CompanyOwner->value);

        Auth::login($user);

        return $user;
    }
}
