<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `prompt_id` était NOT NULL alors que chaque Action de génération gère déjà
 * explicitement le cas « aucun prompt actif pour ce scope » en créant le job
 * avec `prompt_id: $prompt?->id` avant de le clôturer en `Failed` — un
 * chemin jusqu'ici jamais exercé par un test, qui faisait échouer l'insertion
 * elle-même (violation NOT NULL) avant même d'atteindre ce traitement
 * applicatif prévu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generation_jobs', function (Blueprint $table) {
            $table->foreignId('prompt_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_generation_jobs', function (Blueprint $table) {
            $table->foreignId('prompt_id')->nullable(false)->change();
        });
    }
};
