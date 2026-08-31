<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            NafNomenclatureSeeder::class,
            ContentSectionSeeder::class,
            SourceSeeder::class,
            PlanSeeder::class,
            AdSlotSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin->assignRole('super_admin');
    }
}
