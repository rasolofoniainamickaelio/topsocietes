<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `triggered_by` complète la journalisation (Phase 03, "qui"). Nullable :
 * un import lancé depuis la CLI serveur (cron, opération manuelle sans
 * session) n'a pas d'utilisateur associé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('triggered_by')->nullable()->after('mapping_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('triggered_by');
        });
    }
};
