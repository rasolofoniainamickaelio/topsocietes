<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique en écrasement des contenus mutualisés (Phase 08) :
 * `GenerateCityContentAction`/le pipeline IA (Phase 10) écrasent la ligne
 * courante à chaque régénération (`updateOrCreate`) — cette table garde une
 * trace de ce qu'elle contenait juste avant. Immuable (pas d'`updated_at`),
 * comme `ai_generation_logs`/`contact_visibility_events`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->unsignedBigInteger('content_id');
            $table->string('locale');
            $table->string('section');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('status');
            $table->foreignId('generation_id')->nullable()->constrained('ai_generation_jobs')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['content_type', 'content_id'], 'idx_content_revisions_content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
