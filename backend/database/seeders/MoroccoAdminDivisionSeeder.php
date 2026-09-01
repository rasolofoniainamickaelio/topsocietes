<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Les 12 régions du Maroc (découpage territorial de 2015, numérotation
 * officielle du Haut-Commissariat au Plan). Niveau préfecture/province
 * volontairement absent pour l'instant : la liste officielle complète
 * (~80 entités) n'est pas encore vérifiée, on ne fabrique pas de codes
 * non sourcés — les villes importées seront rattachées directement à
 * leur région (niveau 1) jusqu'à ce que ce niveau 2 soit construit à
 * partir d'une vraie source.
 */
class MoroccoAdminDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('code', 'MA')->first();

        if ($country === null) {
            return;
        }

        $regions = [
            ['code' => '01', 'name' => 'Tanger-Tétouan-Al Hoceïma'],
            ['code' => '02', 'name' => "L'Oriental"],
            ['code' => '03', 'name' => 'Fès-Meknès'],
            ['code' => '04', 'name' => 'Rabat-Salé-Kénitra'],
            ['code' => '05', 'name' => 'Béni Mellal-Khénifra'],
            ['code' => '06', 'name' => 'Casablanca-Settat'],
            ['code' => '07', 'name' => 'Marrakech-Safi'],
            ['code' => '08', 'name' => 'Drâa-Tafilalet'],
            ['code' => '09', 'name' => 'Souss-Massa'],
            ['code' => '10', 'name' => 'Guelmim-Oued Noun'],
            ['code' => '11', 'name' => 'Laâyoune-Sakia El Hamra'],
            ['code' => '12', 'name' => 'Dakhla-Oued Ed-Dahab'],
        ];

        foreach ($regions as $region) {
            AdminDivision::query()->updateOrCreate(
                ['country_id' => $country->id, 'level' => 1, 'code' => $region['code']],
                ['name' => $region['name'], 'slug' => Str::slug($region['name'])],
            );
        }
    }
}
