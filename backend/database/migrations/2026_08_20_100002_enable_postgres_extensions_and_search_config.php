<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Active les extensions PostgreSQL requises par le schéma (PostGIS pour la
 * géographie, pg_trgm pour la recherche floue/autocomplete, unaccent pour
 * les slugs et la recherche sans accents, btree_gin pour des index
 * composites GIN, citext pour les emails insensibles à la casse) et crée
 * une configuration de recherche texte immuable `french_unaccent` utilisée
 * par les colonnes `search_vector` générées des tables suivantes.
 *
 * Placée après `countries` (et non en tout premier) pour que la toute
 * première migration reste un Schema Builder pur, sans DB::statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gin');
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        // CREATE TEXT SEARCH CONFIGURATION n'accepte pas IF NOT EXISTS :
        // on garde la migration idempotente via un bloc DO + vérification catalogue.
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'french_unaccent') THEN
                    EXECUTE 'CREATE TEXT SEARCH CONFIGURATION french_unaccent (COPY = french)';
                    EXECUTE 'ALTER TEXT SEARCH CONFIGURATION french_unaccent
                        ALTER MAPPING FOR hword, hword_part, word WITH unaccent, french_stem';
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        // On ne désinstalle pas les extensions (risque sur des objets tiers) :
        // seule la configuration de recherche créée par cette migration est retirée.
        DB::statement('DROP TEXT SEARCH CONFIGURATION IF EXISTS french_unaccent');
    }
};
