<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Les 5 rôles du back-office/front compte, sans permission fine attribuée
 * ici (périmètre Filament/policies hors de cette phase de schéma).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'moderator', 'content_manager', 'company_owner'] as $role) {
            Role::query()->firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
