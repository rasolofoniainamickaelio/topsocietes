<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\ActivityNomenclature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Nomenclature NAF Rev. 2 (INSEE) : 21 sections + 88 divisions — le
 * minimum exploitable pour la navigation par secteur. Les niveaux plus
 * fins (groupe/classe/sous-classe, ~700 postes) sont alimentés par le
 * pipeline d'import (Domaine H), pas par ce seeder. Les libellés sont
 * reproduits de mémoire depuis la nomenclature publique INSEE et doivent
 * être recoupés avec le fichier officiel avant tout usage en production
 * (voir docs/DATABASE.md).
 */
class NafNomenclatureSeeder extends Seeder
{
    /** @var array<string, array{name: string, divisions: array<string, string>}> */
    private array $sections = [
        'A' => ['name' => 'Agriculture, sylviculture et pêche', 'divisions' => [
            '01' => 'Culture et production animale, chasse et services annexes',
            '02' => 'Sylviculture et exploitation forestière',
            '03' => 'Pêche et aquaculture',
        ]],
        'B' => ['name' => 'Industries extractives', 'divisions' => [
            '05' => 'Extraction de houille et de lignite',
            '06' => 'Extraction d\'hydrocarbures',
            '07' => 'Extraction de minerais métalliques',
            '08' => 'Autres industries extractives',
            '09' => 'Services de soutien aux industries extractives',
        ]],
        'C' => ['name' => 'Industrie manufacturière', 'divisions' => [
            '10' => 'Industries alimentaires',
            '11' => 'Fabrication de boissons',
            '12' => 'Fabrication de produits à base de tabac',
            '13' => 'Fabrication de textiles',
            '14' => 'Industrie de l\'habillement',
            '15' => 'Industrie du cuir et de la chaussure',
            '16' => 'Travail du bois et fabrication d\'articles en bois',
            '17' => 'Industrie du papier et du carton',
            '18' => 'Imprimerie et reproduction d\'enregistrements',
            '19' => 'Cokéfaction et raffinage',
            '20' => 'Industrie chimique',
            '21' => 'Industrie pharmaceutique',
            '22' => 'Fabrication de produits en caoutchouc et en plastique',
            '23' => 'Fabrication d\'autres produits minéraux non métalliques',
            '24' => 'Métallurgie',
            '25' => 'Fabrication de produits métalliques, à l\'exception des machines et des équipements',
            '26' => 'Fabrication de produits informatiques, électroniques et optiques',
            '27' => 'Fabrication d\'équipements électriques',
            '28' => 'Fabrication de machines et équipements n.c.a.',
            '29' => 'Industrie automobile',
            '30' => 'Fabrication d\'autres matériels de transport',
            '31' => 'Fabrication de meubles',
            '32' => 'Autres industries manufacturières',
            '33' => 'Réparation et installation de machines et d\'équipements',
        ]],
        'D' => ['name' => 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné', 'divisions' => [
            '35' => 'Production et distribution d\'électricité, de gaz, de vapeur et d\'air conditionné',
        ]],
        'E' => ['name' => 'Production et distribution d\'eau, assainissement, gestion des déchets et dépollution', 'divisions' => [
            '36' => 'Captage, traitement et distribution d\'eau',
            '37' => 'Collecte et traitement des eaux usées',
            '38' => 'Collecte, traitement et élimination des déchets, récupération',
            '39' => 'Dépollution et autres services de gestion des déchets',
        ]],
        'F' => ['name' => 'Construction', 'divisions' => [
            '41' => 'Construction de bâtiments',
            '42' => 'Génie civil',
            '43' => 'Travaux de construction spécialisés',
        ]],
        'G' => ['name' => 'Commerce, réparation d\'automobiles et de motocycles', 'divisions' => [
            '45' => 'Commerce et réparation d\'automobiles et de motocycles',
            '46' => 'Commerce de gros, à l\'exception des automobiles et des motocycles',
            '47' => 'Commerce de détail, à l\'exception des automobiles et des motocycles',
        ]],
        'H' => ['name' => 'Transports et entreposage', 'divisions' => [
            '49' => 'Transports terrestres et transport par conduites',
            '50' => 'Transports par eau',
            '51' => 'Transports aériens',
            '52' => 'Entreposage et services auxiliaires des transports',
            '53' => 'Activités de poste et de courrier',
        ]],
        'I' => ['name' => 'Hébergement et restauration', 'divisions' => [
            '55' => 'Hébergement',
            '56' => 'Restauration',
        ]],
        'J' => ['name' => 'Information et communication', 'divisions' => [
            '58' => 'Édition',
            '59' => 'Production de films cinématographiques, de vidéo et de programmes de télévision, enregistrement sonore et édition musicale',
            '60' => 'Programmation et diffusion',
            '61' => 'Télécommunications',
            '62' => 'Programmation, conseil et autres activités informatiques',
            '63' => 'Services d\'information',
        ]],
        'K' => ['name' => 'Activités financières et d\'assurance', 'divisions' => [
            '64' => 'Activités des services financiers, hors assurance et caisses de retraite',
            '65' => 'Assurance',
            '66' => 'Activités auxiliaires de services financiers et d\'assurance',
        ]],
        'L' => ['name' => 'Activités immobilières', 'divisions' => [
            '68' => 'Activités immobilières',
        ]],
        'M' => ['name' => 'Activités spécialisées, scientifiques et techniques', 'divisions' => [
            '69' => 'Activités juridiques et comptables',
            '70' => 'Activités des sièges sociaux, conseil de gestion',
            '71' => 'Activités d\'architecture et d\'ingénierie, activités de contrôle et analyses techniques',
            '72' => 'Recherche-développement scientifique',
            '73' => 'Publicité et études de marché',
            '74' => 'Autres activités spécialisées, scientifiques et techniques',
            '75' => 'Activités vétérinaires',
        ]],
        'N' => ['name' => 'Activités de services administratifs et de soutien', 'divisions' => [
            '77' => 'Activités de location et location-bail',
            '78' => 'Activités liées à l\'emploi',
            '79' => 'Activités des agences de voyage, voyagistes, services de réservation et activités connexes',
            '80' => 'Enquêtes et sécurité',
            '81' => 'Services relatifs aux bâtiments et aménagement paysager',
            '82' => 'Activités administratives et autres activités de soutien aux entreprises',
        ]],
        'O' => ['name' => 'Administration publique', 'divisions' => [
            '84' => 'Administration publique et défense, sécurité sociale obligatoire',
        ]],
        'P' => ['name' => 'Enseignement', 'divisions' => [
            '85' => 'Enseignement',
        ]],
        'Q' => ['name' => 'Santé humaine et action sociale', 'divisions' => [
            '86' => 'Activités pour la santé humaine',
            '87' => 'Hébergement médico-social et social',
            '88' => 'Action sociale sans hébergement',
        ]],
        'R' => ['name' => 'Arts, spectacles et activités récréatives', 'divisions' => [
            '90' => 'Activités créatives, artistiques et de spectacle',
            '91' => 'Bibliothèques, archives, musées et autres activités culturelles',
            '92' => 'Organisation de jeux de hasard et d\'argent',
            '93' => 'Activités sportives, récréatives et de loisirs',
        ]],
        'S' => ['name' => 'Autres activités de services', 'divisions' => [
            '94' => 'Activités des organisations associatives',
            '95' => 'Réparation d\'ordinateurs et de biens personnels et domestiques',
            '96' => 'Autres services personnels',
        ]],
        'T' => ['name' => 'Activités des ménages en tant qu\'employeurs, activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre', 'divisions' => [
            '97' => 'Activités des ménages en tant qu\'employeurs de personnel domestique',
            '98' => 'Activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre',
        ]],
        'U' => ['name' => 'Activités extra-territoriales', 'divisions' => [
            '99' => 'Activités des organisations et organismes extraterritoriaux',
        ]],
    ];

    public function run(): void
    {
        $countryId = Country::query()->where('code', 'FR')->value('id');

        $nomenclature = ActivityNomenclature::query()->updateOrCreate(
            ['code' => 'NAF'],
            ['name' => 'NAF Rev. 2 (INSEE)', 'country_id' => $countryId, 'version' => '2008']
        );

        foreach ($this->sections as $sectionCode => $section) {
            $sectionModel = Activity::query()->updateOrCreate(
                ['nomenclature_id' => $nomenclature->id, 'code' => $sectionCode],
                [
                    'parent_id' => null,
                    'level' => 1,
                    'label' => $section['name'],
                    'public_label' => $section['name'],
                    'slug' => Str::slug($sectionCode.'-'.$section['name']),
                    'is_publishable' => true,
                ]
            );

            foreach ($section['divisions'] as $divisionCode => $label) {
                Activity::query()->updateOrCreate(
                    ['nomenclature_id' => $nomenclature->id, 'code' => $divisionCode],
                    [
                        'parent_id' => $sectionModel->id,
                        'level' => 2,
                        'label' => $label,
                        'public_label' => $label,
                        'slug' => Str::slug($divisionCode.'-'.$label),
                        'is_publishable' => true,
                    ]
                );
            }
        }
    }
}
