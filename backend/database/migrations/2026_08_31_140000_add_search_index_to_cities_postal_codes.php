<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `cities.postal_codes` (varchar[] natif) n'avait aucun index (Phase 2) :
 * une recherche par code postal (Phase 14, `? = ANY(postal_codes)`) ferait
 * un scan séquentiel. `btree_gin` est déjà actif (Phase 2), il permet un
 * GIN sur un type array standard sans dépendance supplémentaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX gin_cities_postal_codes ON cities USING GIN (postal_codes)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS gin_cities_postal_codes');
    }
};
