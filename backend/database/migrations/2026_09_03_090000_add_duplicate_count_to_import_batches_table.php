<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compte les lignes qui référencent un `national_id` déjà rencontré dans le
 * MÊME lot (doublon intra-fichier) — distinct de `updated_count`/
 * `skipped_count`, qui mesurent des lignes face à des entreprises déjà
 * existantes en base avant ce lot (Phase 03, "détection des doublons").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->integer('duplicate_count')->default(0)->after('skipped_count');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('duplicate_count');
        });
    }
};
