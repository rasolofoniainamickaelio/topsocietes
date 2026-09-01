<?php

declare(strict_types=1);

namespace App\Domain\Import\Support;

/**
 * Table de correspondance construite à la main à partir d'une analyse
 * réelle de `docs/MAROC_ENTREPRISE.xlsx` (492k lignes) — voir l'échange
 * qui a produit ce fichier. Deux formats d'adresse coexistent dans le
 * fichier source :
 *
 * 1. Multi-ligne (~10 % des lignes) : rue \r\n région \r\n ville - code
 *    postal - pays. `REGION_ALIASES` normalise les variantes de nom de
 *    région rencontrées (accents, orthographe FR/EN) vers le code à 2
 *    chiffres de `MoroccoAdminDivisionSeeder`.
 * 2. Une seule ligne (~90 % des lignes) : "...adresse... - Étiquette
 *    Maroc", où l'étiquette finale est un libellé administratif OMPIC —
 *    suffixe `(M)` = municipalité (la ville elle-même), suffixe `(AR)` =
 *    arrondissement (quartier d'une grande ville). `LABEL_MAP` couvre les
 *    ~180 étiquettes les plus fréquentes (~95 % des lignes au format
 *    single-line). Le reste (étiquettes rares, composées de plusieurs
 *    communes comme "Inezgane-Ait Melloul", ou chaînes corrompues dans le
 *    fichier source) est volontairement exclu plutôt que deviné : ces
 *    entreprises seront importées sans ville rattachée.
 *
 * ⚠️ Cette table est une estimation manuelle à partir de connaissances
 * géographiques générales, PAS une source officielle vérifiée. À faire
 * relire avant mise en production (cohérent avec la remarque déjà
 * présente sur `CountrySeeder::$identifier_config` pour l'ICE).
 */
final class MoroccoLocationLabels
{
    /** @return array<string, string> nom de région (normalisé) => code admin_divisions */
    public static function regionAliases(): array
    {
        return [
            'tanger-tetouan-al hoceima' => '01',
            'oriental' => '02',
            "l'oriental" => '02',
            'fes-meknes' => '03',
            'rabat-sale-kenitra' => '04',
            'beni mellal-khenifra' => '05',
            'casablanca-settat' => '06',
            'marrakech-safi' => '07',
            'marrakesh-safi' => '07',
            'draa-tafilalet' => '08',
            'souss-massa' => '09',
            'guelmim-oued noun' => '10',
            'laayoune-sakia el hamra' => '11',
            'dakhla-oued ed-dahab' => '12',
        ];
    }

    /**
     * Clé = étiquette brute telle que rencontrée dans le fichier (après retrait du
     * suffixe pays), avant tout trim/normalisation supplémentaire côté appelant.
     *
     * @return array<string, array{city: string, region: string, district: string|null}>
     */
    public static function labelMap(): array
    {
        return [
            // --- Casablanca (région 06) et ses arrondissements ---
            'Casablanca' => ['city' => 'Casablanca', 'region' => '06', 'district' => null],
            'Casablanca (M)' => ['city' => 'Casablanca', 'region' => '06', 'district' => null],
            'Sidi Bernoussi (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Sidi Bernoussi'],
            'Sidi Moumen (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Sidi Moumen'],
            'Aîn-Sebaâ (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Aïn Sebaâ'],
            'Aîn-Chock (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Aïn Chock'],
            'El Maarif (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Maârif'],
            'Hay Hassani (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Hay Hassani'],
            'Hay-Hassani (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Hay Hassani'],
            'Hay Mohammadi (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Hay Mohammadi'],
            'Ben M\'Sick (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Ben M\'Sick'],
            'Moulay Rachid (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Moulay Rachid'],
            'Sidi Belyout (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Sidi Belyout'],
            'Al-Fida (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Al Fida'],
            'Anfa (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Anfa'],
            'Mers-Sultan (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Mers Sultan'],
            'Sidi Othmane (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Sidi Othmane'],
            'Assoukhour Assawda (AR)' => ['city' => 'Casablanca', 'region' => '06', 'district' => 'Assoukhour Assawda'],
            'Mediouna (M)' => ['city' => 'Mediouna', 'region' => '06', 'district' => null],
            'Bni Yakhlef' => ['city' => 'Mohammedia', 'region' => '06', 'district' => null],
            'Deroua' => ['city' => 'Berrechid', 'region' => '06', 'district' => null],
            'Zemamra (M)' => ['city' => 'Zemamra', 'region' => '06', 'district' => null],
            'Moulay Abdallah' => ['city' => 'El Jadida', 'region' => '06', 'district' => null],
            'Dar Bouazza' => ['city' => 'Dar Bouazza', 'region' => '06', 'district' => null],
            'Bouskoura' => ['city' => 'Bouskoura', 'region' => '06', 'district' => null],
            'Tit Mellil (M)' => ['city' => 'Tit Mellil', 'region' => '06', 'district' => null],
            'Mohammedia Maroc (M)' => ['city' => 'Mohammedia', 'region' => '06', 'district' => null],
            'Mohammedia' => ['city' => 'Mohammedia', 'region' => '06', 'district' => null],
            'Benslimane' => ['city' => 'Benslimane', 'region' => '06', 'district' => null],
            'Benslimane (M)' => ['city' => 'Benslimane', 'region' => '06', 'district' => null],
            'Benslimane Maroc (M)' => ['city' => 'Benslimane', 'region' => '06', 'district' => null],
            'Berrechid (M)' => ['city' => 'Berrechid', 'region' => '06', 'district' => null],
            'Settat' => ['city' => 'Settat', 'region' => '06', 'district' => null],
            'Settat Maroc (M)' => ['city' => 'Settat', 'region' => '06', 'district' => null],
            'El-Jadida Maroc (M)' => ['city' => 'El Jadida', 'region' => '06', 'district' => null],
            'El-Jadida' => ['city' => 'El Jadida', 'region' => '06', 'district' => null],
            'Azemmour (M)' => ['city' => 'Azemmour', 'region' => '06', 'district' => null],
            'Sidi Bennour (M)' => ['city' => 'Sidi Bennour', 'region' => '06', 'district' => null],
            'Bouznika (M)' => ['city' => 'Bouznika', 'region' => '06', 'district' => null],

            // --- Rabat (région 04) et ses arrondissements ---
            'Rabat' => ['city' => 'Rabat', 'region' => '04', 'district' => null],
            'Agdal Riyad (AR)' => ['city' => 'Rabat', 'region' => '04', 'district' => 'Agdal Riyad'],
            'Hassan (AR)' => ['city' => 'Rabat', 'region' => '04', 'district' => 'Hassan'],
            'Yacoub El Mansour (AR)' => ['city' => 'Rabat', 'region' => '04', 'district' => 'Yacoub El Mansour'],
            'Souissi (AR)' => ['city' => 'Rabat', 'region' => '04', 'district' => 'Souissi'],
            // Salé — préfecture voisine de Rabat, arrondissements distincts
            'Sale' => ['city' => 'Salé', 'region' => '04', 'district' => null],
            'Tabriquet (AR)' => ['city' => 'Salé', 'region' => '04', 'district' => 'Tabriquet'],
            'Bettana (AR)' => ['city' => 'Salé', 'region' => '04', 'district' => 'Bettana'],
            'Layayda (AR)' => ['city' => 'Salé', 'region' => '04', 'district' => 'Layayda'],
            'Kénitra Maroc (M)' => ['city' => 'Kénitra', 'region' => '04', 'district' => null],
            'Kénitra' => ['city' => 'Kénitra', 'region' => '04', 'district' => null],
            'Temara Maroc (M)' => ['city' => 'Témara', 'region' => '04', 'district' => null],
            'Temara (M)' => ['city' => 'Témara', 'region' => '04', 'district' => null],
            'Harhoura (M)' => ['city' => 'Témara', 'region' => '04', 'district' => null],
            'Ain Attig' => ['city' => 'Témara', 'region' => '04', 'district' => null],
            'Ain Harrouda(M)' => ['city' => 'Mohammedia', 'region' => '06', 'district' => null],
            'Skhirate (M)' => ['city' => 'Skhirat', 'region' => '04', 'district' => null],
            'Khémisset (M)' => ['city' => 'Khémisset', 'region' => '04', 'district' => null],
            'Khemisset' => ['city' => 'Khémisset', 'region' => '04', 'district' => null],
            'Sidi Yahya El Gharb (M)' => ['city' => 'Sidi Yahya El Gharb', 'region' => '04', 'district' => null],
            'Sidi Allal El Bahraoui' => ['city' => 'Khémisset', 'region' => '04', 'district' => null],
            'Jorf El Melha (M)' => ['city' => 'Khémisset', 'region' => '04', 'district' => null],
            'Ain El Aouda (M)' => ['city' => 'Témara', 'region' => '04', 'district' => null],

            // --- Marrakech (région 07) et ses arrondissements ---
            'Marrakech' => ['city' => 'Marrakech', 'region' => '07', 'district' => null],
            'Marrakech Maroc-Médina (AR)' => ['city' => 'Marrakech', 'region' => '07', 'district' => 'Médina'],
            'Gueliz (AR)' => ['city' => 'Marrakech', 'region' => '07', 'district' => 'Guéliz'],
            'Ménara (AR)' => ['city' => 'Marrakech', 'region' => '07', 'district' => 'Ménara'],
            'Sidi Youssef Ben Ali (AR)' => ['city' => 'Marrakech', 'region' => '07', 'district' => 'Sidi Youssef Ben Ali'],
            'Sidi Ghanem' => ['city' => 'Marrakech', 'region' => '07', 'district' => null],
            'Saada' => ['city' => 'Marrakech', 'region' => '07', 'district' => null],
            'Amizmiz' => ['city' => 'Amizmiz', 'region' => '07', 'district' => null],
            'Tahannaout' => ['city' => 'Tahannaout', 'region' => '07', 'district' => null],
            'Chichaoua (M)' => ['city' => 'Chichaoua', 'region' => '07', 'district' => null],
            'Safi' => ['city' => 'Safi', 'region' => '07', 'district' => null],
            'Safi Maroc (M)' => ['city' => 'Safi', 'region' => '07', 'district' => null],
            'Essaouira Maroc (M)' => ['city' => 'Essaouira', 'region' => '07', 'district' => null],
            'Essaouira (M)' => ['city' => 'Essaouira', 'region' => '07', 'district' => null],
            'Essaouira' => ['city' => 'Essaouira', 'region' => '07', 'district' => null],
            'El Kelaâ des Sraghna(M)' => ['city' => 'El Kelaâ des Sraghna', 'region' => '07', 'district' => null],
            'El Kelaa Des Sraghna' => ['city' => 'El Kelaâ des Sraghna', 'region' => '07', 'district' => null],
            'Ben Guerir (M)' => ['city' => 'Ben Guerir', 'region' => '07', 'district' => null],
            'Ait Ourir (M)' => ['city' => 'Ait Ourir', 'region' => '07', 'district' => null],
            'Youssoufia (M)' => ['city' => 'Youssoufia', 'region' => '07', 'district' => null],

            // --- Tanger (région 01) et environs ---
            'Tanger-Médina (AR)' => ['city' => 'Tanger', 'region' => '01', 'district' => 'Médina'],
            'Tanger-Assilah' => ['city' => 'Tanger', 'region' => '01', 'district' => null],
            'Bni Makada (AR)' => ['city' => 'Tanger', 'region' => '01', 'district' => 'Bni Makada'],
            'Boukhalef' => ['city' => 'Tanger', 'region' => '01', 'district' => null],
            'El Marsa (M)' => ['city' => 'Tanger', 'region' => '01', 'district' => null],
            'Assilah (M)' => ['city' => 'Asilah', 'region' => '01', 'district' => null],
            'Tetouan Maroc (M)' => ['city' => 'Tétouan', 'region' => '01', 'district' => null],
            'Tetouan  Maroc (M)' => ['city' => 'Tétouan', 'region' => '01', 'district' => null],
            'Tetouan (M)' => ['city' => 'Tétouan', 'region' => '01', 'district' => null],
            'M\'Diq (M)' => ['city' => 'M\'Diq', 'region' => '01', 'district' => null],
            'Fnidq (M)' => ['city' => 'Fnideq', 'region' => '01', 'district' => null],
            'Chefchaouen Maroc (M)' => ['city' => 'Chefchaouen', 'region' => '01', 'district' => null],
            'Larache Maroc (M)' => ['city' => 'Larache', 'region' => '01', 'district' => null],
            'Larache' => ['city' => 'Larache', 'region' => '01', 'district' => null],
            'Ksar El Kebir (M)' => ['city' => 'Ksar El Kébir', 'region' => '01', 'district' => null],
            'Ouezzane (M)' => ['city' => 'Ouezzane', 'region' => '01', 'district' => null],
            'Al Hoceima (M)' => ['city' => 'Al Hoceïma', 'region' => '01', 'district' => null],
            'Al Hoceima' => ['city' => 'Al Hoceïma', 'region' => '01', 'district' => null],

            // --- Fès (région 03) et ses arrondissements ---
            'Fès Maroc-Médina (AR)' => ['city' => 'Fès', 'region' => '03', 'district' => 'Médina'],
            'Fès' => ['city' => 'Fès', 'region' => '03', 'district' => null],
            'Zouagha (AR)' => ['city' => 'Fès', 'region' => '03', 'district' => 'Zouagha'],
            'Agdal (AR)' => ['city' => 'Fès', 'region' => '03', 'district' => 'Agdal'],
            'Meknès Maroc (M)' => ['city' => 'Meknès', 'region' => '03', 'district' => null],
            'Meknès' => ['city' => 'Meknès', 'region' => '03', 'district' => null],
            'Ouislane (M)' => ['city' => 'Meknès', 'region' => '03', 'district' => null],
            'Sidi-Kacem Maroc (M)' => ['city' => 'Sidi Kacem', 'region' => '03', 'district' => null],
            'Sidi-Kacem' => ['city' => 'Sidi Kacem', 'region' => '03', 'district' => null],
            'Taza Maroc (M)' => ['city' => 'Taza', 'region' => '03', 'district' => null],
            'Taza' => ['city' => 'Taza', 'region' => '03', 'district' => null],
            'Taza (M)' => ['city' => 'Taza', 'region' => '03', 'district' => null],
            'Taounate (M)' => ['city' => 'Taounate', 'region' => '03', 'district' => null],
            'Taounate' => ['city' => 'Taounate', 'region' => '03', 'district' => null],
            'Sefrou Maroc (M)' => ['city' => 'Sefrou', 'region' => '03', 'district' => null],
            'Sefrou (M)' => ['city' => 'Sefrou', 'region' => '03', 'district' => null],
            'Azrou (M)' => ['city' => 'Azrou', 'region' => '03', 'district' => null],
            'Ain Taoujdate (M)' => ['city' => 'Ain Taoujdate', 'region' => '03', 'district' => null],
            'Missour (M)' => ['city' => 'Missour', 'region' => '03', 'district' => null],
            'Oued Amlil (M)' => ['city' => 'Oued Amlil', 'region' => '03', 'district' => null],
            'Mechra Bel Ksiri (M)' => ['city' => 'Mechra Bel Ksiri', 'region' => '03', 'district' => null],
            'El Hajeb (M)' => ['city' => 'El Hajeb', 'region' => '03', 'district' => null],

            // --- Agadir / Souss-Massa (région 09) ---
            'Agadir (M)' => ['city' => 'Agadir', 'region' => '09', 'district' => null],
            'Agadir-Ida Ou Tanane' => ['city' => 'Agadir', 'region' => '09', 'district' => null],
            'Dcheira El Jihadia (M)' => ['city' => 'Dcheira El Jihadia', 'region' => '09', 'district' => null],
            'Ait Melloul Maroc (M)' => ['city' => 'Ait Melloul', 'region' => '09', 'district' => null],
            'Ait Melloul (M)' => ['city' => 'Ait Melloul', 'region' => '09', 'district' => null],
            'Inezgane (M)' => ['city' => 'Inezgane', 'region' => '09', 'district' => null],
            'Drargua' => ['city' => 'Agadir', 'region' => '09', 'district' => null],
            'Biougra (M)' => ['city' => 'Biougra', 'region' => '09', 'district' => null],
            'Oulad Teima (M)' => ['city' => 'Oulad Teima', 'region' => '09', 'district' => null],
            'Taroudannt' => ['city' => 'Taroudant', 'region' => '09', 'district' => null],
            'Taroudannt (M)' => ['city' => 'Taroudant', 'region' => '09', 'district' => null],
            'Taroudannt Maroc (M)' => ['city' => 'Taroudant', 'region' => '09', 'district' => null],
            'Tiznit Maroc (M)' => ['city' => 'Tiznit', 'region' => '09', 'district' => null],
            'Tiznit' => ['city' => 'Tiznit', 'region' => '09', 'district' => null],
            'Tata (M)' => ['city' => 'Tata', 'region' => '09', 'district' => null],

            // --- Oriental (région 02) ---
            'Oujda (M)' => ['city' => 'Oujda', 'region' => '02', 'district' => null],
            'Oujda-Angad' => ['city' => 'Oujda', 'region' => '02', 'district' => null],
            'Nador Maroc (M)' => ['city' => 'Nador', 'region' => '02', 'district' => null],
            'Nador' => ['city' => 'Nador', 'region' => '02', 'district' => null],
            'Nador (M)' => ['city' => 'Nador', 'region' => '02', 'district' => null],
            'Bni Ansar (M)' => ['city' => 'Beni Ansar', 'region' => '02', 'district' => null],
            'Selouane' => ['city' => 'Selouane', 'region' => '02', 'district' => null],
            'Berkane (M)' => ['city' => 'Berkane', 'region' => '02', 'district' => null],
            'Berkane' => ['city' => 'Berkane', 'region' => '02', 'district' => null],
            'Berkane Maroc (M)' => ['city' => 'Berkane', 'region' => '02', 'district' => null],
            'Zaio (M)' => ['city' => 'Zaio', 'region' => '02', 'district' => null],
            'Guercif (M)' => ['city' => 'Guercif', 'region' => '02', 'district' => null],
            'Driouch' => ['city' => 'Driouch', 'region' => '02', 'district' => null],
            'Outat El Haj (M)' => ['city' => 'Outat El Haj', 'region' => '02', 'district' => null],

            // --- Béni Mellal-Khénifra (région 05) ---
            'Beni Mellal (M)' => ['city' => 'Béni Mellal', 'region' => '05', 'district' => null],
            'Beni Mellal' => ['city' => 'Béni Mellal', 'region' => '05', 'district' => null],
            'Khenifra Maroc (M)' => ['city' => 'Khénifra', 'region' => '05', 'district' => null],
            'Khenifra' => ['city' => 'Khénifra', 'region' => '05', 'district' => null],
            'Khouribga (M)' => ['city' => 'Khouribga', 'region' => '05', 'district' => null],
            'Khouribga' => ['city' => 'Khouribga', 'region' => '05', 'district' => null],
            'Khouribga Maroc (M)' => ['city' => 'Khouribga', 'region' => '05', 'district' => null],
            'Fquih Ben Salah (M)' => ['city' => 'Fquih Ben Salah', 'region' => '05', 'district' => null],
            'Oued Zem (M)' => ['city' => 'Oued Zem', 'region' => '05', 'district' => null],
            'Kasba Tadla (M)' => ['city' => 'Kasba Tadla', 'region' => '05', 'district' => null],
            'El Ksiba (M)' => ['city' => 'El Ksiba', 'region' => '05', 'district' => null],
            'Demnate (M)' => ['city' => 'Demnate', 'region' => '05', 'district' => null],
            'Azilal Maroc (M)' => ['city' => 'Azilal', 'region' => '05', 'district' => null],
            'Azilal' => ['city' => 'Azilal', 'region' => '05', 'district' => null],
            'Souk Sebt Oulad Nemma (M)' => ['city' => 'Souk Sebt Oulad Nemma', 'region' => '05', 'district' => null],

            // --- Drâa-Tafilalet (région 08) ---
            'Ouarzazate Maroc (M)' => ['city' => 'Ouarzazate', 'region' => '08', 'district' => null],
            'Ouarzazate' => ['city' => 'Ouarzazate', 'region' => '08', 'district' => null],
            'Errachidia (M)' => ['city' => 'Errachidia', 'region' => '08', 'district' => null],
            'Errachidia' => ['city' => 'Errachidia', 'region' => '08', 'district' => null],
            'Errachidia Maroc (M)' => ['city' => 'Errachidia', 'region' => '08', 'district' => null],
            'Er-rissani' => ['city' => 'Rissani', 'region' => '08', 'district' => null],
            'Zagora (M)' => ['city' => 'Zagora', 'region' => '08', 'district' => null],
            'Tinghir (M)' => ['city' => 'Tinghir', 'region' => '08', 'district' => null],
            'Arfoud (M)' => ['city' => 'Erfoud', 'region' => '08', 'district' => null],
            'Tinejdad (M)' => ['city' => 'Tinejdad', 'region' => '08', 'district' => null],
            'Midelt (M)' => ['city' => 'Midelt', 'region' => '08', 'district' => null],
            'Tarmigt' => ['city' => 'Ouarzazate', 'region' => '08', 'district' => null],

            // --- Guelmim-Oued Noun (région 10) ---
            'Guelmim Maroc (M)' => ['city' => 'Guelmim', 'region' => '10', 'district' => null],
            'Sidi Ifni (M)' => ['city' => 'Sidi Ifni', 'region' => '10', 'district' => null],
            'Tan Tan (M)' => ['city' => 'Tan-Tan', 'region' => '10', 'district' => null],
            'Tan-Tan' => ['city' => 'Tan-Tan', 'region' => '10', 'district' => null],

            // --- Laâyoune-Sakia El Hamra (région 11) ---
            'Laayoune Maroc (M)' => ['city' => 'Laâyoune', 'region' => '11', 'district' => null],
            'Laayoune' => ['city' => 'Laâyoune', 'region' => '11', 'district' => null],
            'Boujdour (M)' => ['city' => 'Boujdour', 'region' => '11', 'district' => null],
            'Es-semara (M)' => ['city' => 'Es-Semara', 'region' => '11', 'district' => null],
            'Es-Semara' => ['city' => 'Es-Semara', 'region' => '11', 'district' => null],

            // --- Dakhla-Oued Ed-Dahab (région 12) ---
            'Dakhla (M)' => ['city' => 'Dakhla', 'region' => '12', 'district' => null],
            'Oued-Ed-Dahab' => ['city' => 'Dakhla', 'region' => '12', 'district' => null],

            // --- Souk El Arbaa (région 04, province Kénitra) ---
            'Souk El Arbaa (M)' => ['city' => 'Souk El Arbaa', 'region' => '04', 'district' => null],
        ];
    }
}
