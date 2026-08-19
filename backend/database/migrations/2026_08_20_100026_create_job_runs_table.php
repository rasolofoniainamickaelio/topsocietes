<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'exécution des jobs d'exploitation (import, génération IA,
 * sitemaps, etc.), consommé par le back-office Filament pour le suivi
 * opérationnel. Distinct de `jobs`/`failed_jobs` (file d'attente Laravel,
 * éphémère) : `job_runs` est un historique durable et interrogeable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('queue')->nullable();
            $table->string('status');
            $table->jsonb('payload')->nullable();
            $table->jsonb('output')->nullable();
            $table->text('exception')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->integer('progress_current')->nullable();
            $table->integer('progress_total')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['name', 'status'], 'idx_job_runs_name_status');
            $table->index(['status', 'created_at'], 'idx_job_runs_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};
