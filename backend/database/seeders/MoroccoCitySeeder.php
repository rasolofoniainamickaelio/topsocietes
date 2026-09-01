<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Geo\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les 97 villes et 31 quartiers déduits de `MoroccoLocationLabels`
 * (couvrant ~92 % des lignes de `docs/MAROC_ENTREPRISE.xlsx`), géocodés
 * une fois via Nominatim/OSM (cf. l'échange qui a produit ce fichier —
 * deux résultats aberrants corrigés manuellement après vérification :
 * Mediouna et l'arrondissement Tanger-Médina avaient été mal désambiguïsés
 * par la recherche textuelle). `location` est NOT NULL sur `cities`
 * (contrairement aux entreprises, une commune vient toujours d'un
 * référentiel qui fournit ses coordonnées) : ce seeder est donc un
 * préalable obligatoire à tout import réel de sociétés marocaines,
 * exécuté une seule fois plutôt qu'à la volée pendant l'import (par lots
 * parallélisés — créer une ville pendant l'import exposerait à des races
 * conditions sur la contrainte d'unicité `(country_id, slug)`).
 */
class MoroccoCitySeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('code', 'MA')->first();

        if ($country === null) {
            return;
        }

        $regionByCode = AdminDivision::query()
            ->where('country_id', $country->id)
            ->where('level', 1)
            ->pluck('id', 'code');

        $cityIdByName = [];

        foreach (self::cities() as $entry) {
            $slug = Str::slug($entry['name']);

            // `location` est NOT NULL sur `cities` : impossible de créer
            // puis mettre à jour en 2 requêtes (create() échouerait avant
            // même d'atteindre l'UPDATE). Elle doit être posée dans le
            // même statement que l'insertion.
            $existing = City::query()->where('country_id', $country->id)->where('slug', $slug)->first();

            if ($existing !== null) {
                $existing->update([
                    'admin_division_id' => $regionByCode[$entry['region']] ?? null,
                    'name' => $entry['name'],
                ]);
                $cityIdByName[$entry['name']] = $existing->id;

                continue;
            }

            $cityId = DB::table('cities')->insertGetId([
                'country_id' => $country->id,
                'admin_division_id' => $regionByCode[$entry['region']] ?? null,
                'code' => $slug,
                'name' => $entry['name'],
                'slug' => $slug,
                'companies_count' => 0,
                'has_local_content' => false,
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$entry['lon']}, {$entry['lat']}), 4326)::geography"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cityIdByName[$entry['name']] = $cityId;
        }

        foreach (self::districts() as $entry) {
            $cityId = $cityIdByName[$entry['city']] ?? null;

            if ($cityId === null) {
                continue;
            }

            $slug = Str::slug($entry['name']);
            $existing = District::query()->where('city_id', $cityId)->where('slug', $slug)->first();

            if ($existing !== null) {
                DB::statement(
                    'UPDATE districts SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                    [$entry['lon'], $entry['lat'], $existing->id],
                );

                continue;
            }

            DB::table('districts')->insert([
                'country_id' => $country->id,
                'city_id' => $cityId,
                'name' => $entry['name'],
                'slug' => $slug,
                'companies_count' => 0,
                'has_local_content' => false,
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$entry['lon']}, {$entry['lat']}), 4326)::geography"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array<int, array{name: string, region: string, lat: float, lon: float}> */
    private static function cities(): array
    {
        return [
            ['name' => 'Casablanca', 'region' => '06', 'lat' => 33.5945144, 'lon' => -7.6200284],
            ['name' => 'Mediouna', 'region' => '06', 'lat' => 33.4562510, 'lon' => -7.5194400],
            ['name' => 'Mohammedia', 'region' => '06', 'lat' => 33.6958383, 'lon' => -7.3893292],
            ['name' => 'Berrechid', 'region' => '06', 'lat' => 33.2676746, 'lon' => -7.5811465],
            ['name' => 'Zemamra', 'region' => '06', 'lat' => 32.6161958, 'lon' => -8.7043575],
            ['name' => 'El Jadida', 'region' => '06', 'lat' => 33.2433309, 'lon' => -8.4988400],
            ['name' => 'Dar Bouazza', 'region' => '06', 'lat' => 33.5215831, 'lon' => -7.8164369],
            ['name' => 'Bouskoura', 'region' => '06', 'lat' => 33.4564430, 'lon' => -7.6506660],
            ['name' => 'Tit Mellil', 'region' => '06', 'lat' => 33.5514120, 'lon' => -7.4835520],
            ['name' => 'Benslimane', 'region' => '06', 'lat' => 33.6228983, 'lon' => -7.1267176],
            ['name' => 'Settat', 'region' => '06', 'lat' => 33.0023970, 'lon' => -7.6198670],
            ['name' => 'Azemmour', 'region' => '06', 'lat' => 33.2751698, 'lon' => -8.3430934],
            ['name' => 'Sidi Bennour', 'region' => '06', 'lat' => 32.6507792, 'lon' => -8.4242087],
            ['name' => 'Bouznika', 'region' => '06', 'lat' => 33.7810050, 'lon' => -7.1610294],
            ['name' => 'Rabat', 'region' => '04', 'lat' => 34.0218454, 'lon' => -6.8408929],
            ['name' => 'Salé', 'region' => '04', 'lat' => 34.0448890, 'lon' => -6.8140170],
            ['name' => 'Kénitra', 'region' => '04', 'lat' => 34.2645700, 'lon' => -6.5701690],
            ['name' => 'Témara', 'region' => '04', 'lat' => 33.9171660, 'lon' => -6.9238040],
            ['name' => 'Skhirat', 'region' => '04', 'lat' => 33.8506720, 'lon' => -7.0280260],
            ['name' => 'Khémisset', 'region' => '04', 'lat' => 33.8302870, 'lon' => -6.0726050],
            ['name' => 'Sidi Yahya El Gharb', 'region' => '04', 'lat' => 34.3085169, 'lon' => -6.2940091],
            ['name' => 'Marrakech', 'region' => '07', 'lat' => 31.6258257, 'lon' => -7.9891608],
            ['name' => 'Amizmiz', 'region' => '07', 'lat' => 31.2171012, 'lon' => -8.2332754],
            ['name' => 'Tahannaout', 'region' => '07', 'lat' => 31.3672735, 'lon' => -7.9447676],
            ['name' => 'Chichaoua', 'region' => '07', 'lat' => 31.5463197, 'lon' => -8.7602644],
            ['name' => 'Safi', 'region' => '07', 'lat' => 32.2994240, 'lon' => -9.2395330],
            ['name' => 'Essaouira', 'region' => '07', 'lat' => 31.5118281, 'lon' => -9.7620903],
            ['name' => 'El Kelaâ des Sraghna', 'region' => '07', 'lat' => 32.0538921, 'lon' => -7.4068642],
            ['name' => 'Ben Guerir', 'region' => '07', 'lat' => 32.2390340, 'lon' => -7.9581310],
            ['name' => 'Ait Ourir', 'region' => '07', 'lat' => 31.5638980, 'lon' => -7.6622760],
            ['name' => 'Youssoufia', 'region' => '07', 'lat' => 32.2458010, 'lon' => -8.5324390],
            ['name' => 'Tanger', 'region' => '01', 'lat' => 35.7696302, 'lon' => -5.8033522],
            ['name' => 'Asilah', 'region' => '01', 'lat' => 35.4619280, 'lon' => -6.0365450],
            ['name' => 'Tétouan', 'region' => '01', 'lat' => 35.5697958, 'lon' => -5.3746918],
            ['name' => "M'Diq", 'region' => '01', 'lat' => 35.6833598, 'lon' => -5.3232161],
            ['name' => 'Fnideq', 'region' => '01', 'lat' => 35.8392089, 'lon' => -5.3614246],
            ['name' => 'Chefchaouen', 'region' => '01', 'lat' => 35.1700832, 'lon' => -5.2766583],
            ['name' => 'Larache', 'region' => '01', 'lat' => 35.1952327, 'lon' => -6.1529130],
            ['name' => 'Ksar El Kébir', 'region' => '01', 'lat' => 34.9992180, 'lon' => -5.8987240],
            ['name' => 'Ouezzane', 'region' => '01', 'lat' => 34.7967570, 'lon' => -5.5784930],
            ['name' => 'Al Hoceïma', 'region' => '01', 'lat' => 35.2451140, 'lon' => -3.9301860],
            ['name' => 'Fès', 'region' => '03', 'lat' => 34.0346534, 'lon' => -5.0161926],
            ['name' => 'Meknès', 'region' => '03', 'lat' => 33.8984131, 'lon' => -5.5321582],
            ['name' => 'Sidi Kacem', 'region' => '03', 'lat' => 34.2264120, 'lon' => -5.7114340],
            ['name' => 'Taza', 'region' => '03', 'lat' => 34.2301550, 'lon' => -4.0101040],
            ['name' => 'Taounate', 'region' => '03', 'lat' => 34.5384760, 'lon' => -4.6359940],
            ['name' => 'Sefrou', 'region' => '03', 'lat' => 33.8248980, 'lon' => -4.8333360],
            ['name' => 'Azrou', 'region' => '03', 'lat' => 35.1302400, 'lon' => -3.5443970],
            ['name' => 'Ain Taoujdate', 'region' => '03', 'lat' => 33.9403020, 'lon' => -5.2080520],
            ['name' => 'Missour', 'region' => '03', 'lat' => 33.0470570, 'lon' => -3.9925780],
            ['name' => 'Oued Amlil', 'region' => '03', 'lat' => 34.1915620, 'lon' => -4.2676040],
            ['name' => 'Mechra Bel Ksiri', 'region' => '03', 'lat' => 34.5734160, 'lon' => -5.9566150],
            ['name' => 'El Hajeb', 'region' => '03', 'lat' => 33.6987516, 'lon' => -5.4928593],
            ['name' => 'Agadir', 'region' => '09', 'lat' => 30.4205162, 'lon' => -9.5838532],
            ['name' => 'Dcheira El Jihadia', 'region' => '09', 'lat' => 30.3752810, 'lon' => -9.5284950],
            ['name' => 'Ait Melloul', 'region' => '09', 'lat' => 30.3387947, 'lon' => -9.5044701],
            ['name' => 'Inezgane', 'region' => '09', 'lat' => 30.3562929, 'lon' => -9.5459347],
            ['name' => 'Biougra', 'region' => '09', 'lat' => 30.2155170, 'lon' => -9.3685430],
            ['name' => 'Oulad Teima', 'region' => '09', 'lat' => 30.3954707, 'lon' => -9.2105335],
            ['name' => 'Taroudant', 'region' => '09', 'lat' => 30.4706510, 'lon' => -8.8779220],
            ['name' => 'Tiznit', 'region' => '09', 'lat' => 29.6986240, 'lon' => -9.7312815],
            ['name' => 'Tata', 'region' => '09', 'lat' => 29.7463920, 'lon' => -7.9696051],
            ['name' => 'Oujda', 'region' => '02', 'lat' => 34.6778740, 'lon' => -1.9293060],
            ['name' => 'Nador', 'region' => '02', 'lat' => 35.1739922, 'lon' => -2.9281198],
            ['name' => 'Beni Ansar', 'region' => '02', 'lat' => 35.2596870, 'lon' => -2.9336440],
            ['name' => 'Selouane', 'region' => '02', 'lat' => 35.0728400, 'lon' => -2.9417190],
            ['name' => 'Berkane', 'region' => '02', 'lat' => 34.9266755, 'lon' => -2.3294087],
            ['name' => 'Zaio', 'region' => '02', 'lat' => 34.9419060, 'lon' => -2.7341640],
            ['name' => 'Guercif', 'region' => '02', 'lat' => 34.2255760, 'lon' => -3.3523450],
            ['name' => 'Driouch', 'region' => '02', 'lat' => 34.9769400, 'lon' => -3.3910050],
            ['name' => 'Outat El Haj', 'region' => '02', 'lat' => 33.3373660, 'lon' => -3.6964380],
            ['name' => 'Béni Mellal', 'region' => '05', 'lat' => 32.3341930, 'lon' => -6.3533350],
            ['name' => 'Khénifra', 'region' => '05', 'lat' => 32.9357718, 'lon' => -5.6696504],
            ['name' => 'Khouribga', 'region' => '05', 'lat' => 32.8856482, 'lon' => -6.9087980],
            ['name' => 'Fquih Ben Salah', 'region' => '05', 'lat' => 32.4212148, 'lon' => -6.7470785],
            ['name' => 'Oued Zem', 'region' => '05', 'lat' => 32.8625040, 'lon' => -6.5710540],
            ['name' => 'Kasba Tadla', 'region' => '05', 'lat' => 32.6028940, 'lon' => -6.2697060],
            ['name' => 'El Ksiba', 'region' => '05', 'lat' => 32.5689020, 'lon' => -6.0340930],
            ['name' => 'Demnate', 'region' => '05', 'lat' => 31.7322240, 'lon' => -7.0026760],
            ['name' => 'Azilal', 'region' => '05', 'lat' => 31.9592950, 'lon' => -6.5709910],
            ['name' => 'Souk Sebt Oulad Nemma', 'region' => '05', 'lat' => 32.2947530, 'lon' => -6.7006590],
            ['name' => 'Ouarzazate', 'region' => '08', 'lat' => 30.9201930, 'lon' => -6.9109230],
            ['name' => 'Errachidia', 'region' => '08', 'lat' => 31.9290890, 'lon' => -4.4340807],
            ['name' => 'Rissani', 'region' => '08', 'lat' => 31.2618886, 'lon' => -3.9593135],
            ['name' => 'Zagora', 'region' => '08', 'lat' => 30.3279053, 'lon' => -5.8369957],
            ['name' => 'Tinghir', 'region' => '08', 'lat' => 31.5213300, 'lon' => -5.5311640],
            ['name' => 'Erfoud', 'region' => '08', 'lat' => 31.4349921, 'lon' => -4.2328293],
            ['name' => 'Tinejdad', 'region' => '08', 'lat' => 31.5102010, 'lon' => -5.0338200],
            ['name' => 'Midelt', 'region' => '08', 'lat' => 32.6803470, 'lon' => -4.7398970],
            ['name' => 'Guelmim', 'region' => '10', 'lat' => 28.9863852, 'lon' => -10.0574351],
            ['name' => 'Sidi Ifni', 'region' => '10', 'lat' => 29.3791253, 'lon' => -10.1715632],
            ['name' => 'Tan-Tan', 'region' => '10', 'lat' => 28.4375530, 'lon' => -11.0986640],
            ['name' => 'Laâyoune', 'region' => '11', 'lat' => 27.1545120, 'lon' => -13.1953921],
            ['name' => 'Boujdour', 'region' => '11', 'lat' => 26.1263378, 'lon' => -14.4836537],
            ['name' => 'Es-Semara', 'region' => '11', 'lat' => 26.7435827, 'lon' => -11.6645492],
            ['name' => 'Dakhla', 'region' => '12', 'lat' => 23.6940663, 'lon' => -15.9431274],
            ['name' => 'Souk El Arbaa', 'region' => '04', 'lat' => 34.6765226, 'lon' => -5.9926170],
        ];
    }

    /** @return array<int, array{city: string, name: string, lat: float, lon: float}> */
    private static function districts(): array
    {
        return [
            ['city' => 'Casablanca', 'name' => 'Sidi Bernoussi', 'lat' => 33.6190686, 'lon' => -7.5003234],
            ['city' => 'Casablanca', 'name' => 'Sidi Moumen', 'lat' => 33.5843834, 'lon' => -7.5072051],
            ['city' => 'Casablanca', 'name' => 'Aïn Sebaâ', 'lat' => 33.6059943, 'lon' => -7.5387936],
            ['city' => 'Casablanca', 'name' => 'Aïn Chock', 'lat' => 33.5370603, 'lon' => -7.5981542],
            ['city' => 'Casablanca', 'name' => 'Maârif', 'lat' => 33.5708072, 'lon' => -7.6282984],
            ['city' => 'Casablanca', 'name' => 'Hay Hassani', 'lat' => 33.5470387, 'lon' => -7.6781652],
            ['city' => 'Casablanca', 'name' => 'Hay Mohammadi', 'lat' => 33.5841444, 'lon' => -7.5569559],
            ['city' => 'Casablanca', 'name' => "Ben M'Sick", 'lat' => 33.5542611, 'lon' => -7.5813658],
            ['city' => 'Casablanca', 'name' => 'Moulay Rachid', 'lat' => 33.5675448, 'lon' => -7.5452441],
            ['city' => 'Casablanca', 'name' => 'Sidi Belyout', 'lat' => 33.5959060, 'lon' => -7.6198840],
            ['city' => 'Casablanca', 'name' => 'Al Fida', 'lat' => 33.5649078, 'lon' => -7.5965827],
            ['city' => 'Casablanca', 'name' => 'Anfa', 'lat' => 33.5785572, 'lon' => -7.6905570],
            ['city' => 'Casablanca', 'name' => 'Mers Sultan', 'lat' => 33.5697535, 'lon' => -7.6088390],
            ['city' => 'Casablanca', 'name' => 'Sidi Othmane', 'lat' => 33.5570072, 'lon' => -7.5603844],
            ['city' => 'Casablanca', 'name' => 'Assoukhour Assawda', 'lat' => 33.5931914, 'lon' => -7.5957822],
            ['city' => 'Rabat', 'name' => 'Agdal Riyad', 'lat' => 34.0071036, 'lon' => -6.8458158],
            ['city' => 'Rabat', 'name' => 'Hassan', 'lat' => 34.0139405, 'lon' => -6.8256177],
            ['city' => 'Rabat', 'name' => 'Yacoub El Mansour', 'lat' => 33.9738778, 'lon' => -6.8926131],
            ['city' => 'Rabat', 'name' => 'Souissi', 'lat' => 33.9688418, 'lon' => -6.8348164],
            ['city' => 'Salé', 'name' => 'Tabriquet', 'lat' => 34.0554847, 'lon' => -6.7887259],
            ['city' => 'Salé', 'name' => 'Bettana', 'lat' => 34.0313433, 'lon' => -6.8108383],
            ['city' => 'Salé', 'name' => 'Layayda', 'lat' => 34.0596186, 'lon' => -6.7697982],
            ['city' => 'Marrakech', 'name' => 'Médina', 'lat' => 31.6329766, 'lon' => -7.9884912],
            ['city' => 'Marrakech', 'name' => 'Guéliz', 'lat' => 31.6321881, 'lon' => -8.0108135],
            ['city' => 'Marrakech', 'name' => 'Ménara', 'lat' => 31.6058729, 'lon' => -8.0324568],
            ['city' => 'Marrakech', 'name' => 'Sidi Youssef Ben Ali', 'lat' => 31.6090052, 'lon' => -7.9679007],
            ['city' => 'Tanger', 'name' => 'Médina', 'lat' => 35.7864080, 'lon' => -5.8109634],
            ['city' => 'Tanger', 'name' => 'Bni Makada', 'lat' => 35.7511344, 'lon' => -5.8187652],
            ['city' => 'Fès', 'name' => 'Médina', 'lat' => 34.0631192, 'lon' => -4.9737999],
            ['city' => 'Fès', 'name' => 'Zouagha', 'lat' => 34.0174142, 'lon' => -5.0541572],
            ['city' => 'Fès', 'name' => 'Agdal', 'lat' => 34.0379627, 'lon' => -4.9997468],
        ];
    }
}
