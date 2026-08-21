<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contenu métier/sectoriel, rattaché à `activity_id` OU `sector_id`
 * (jamais les deux, jamais aucun des deux — CHECK explicite), avec
 * `country_id` nullable pour un contenu national spécifique.
 *
 * L'unicité `(sujet, country_id, locale, section)` ne peut pas s'exprimer
 * en une seule contrainte composite classique : `activity_id`/`sector_id`
 * étant mutuellement exclusifs, l'un des deux est toujours NULL, et
 * Postgres ne considère jamais deux NULL comme égaux dans une contrainte
 * UNIQUE standard (deux doublons NULL passeraient donc inaperçus). Deux
 * index UNIQUE partiels (un par branche du XOR) résolvent correctement le
 * problème ; `COALESCE(country_id, 0)` normalise le NULL de `country_id`
 * pour que le contenu "générique" (tous pays) soit lui aussi dédupliqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->restrictOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->restrictOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('locale', 10);
            $table->string('section');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('status');
            $table->smallInteger('quality_score')->nullable();
            $table->unsignedBigInteger('generation_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('generation_id', 'idx_activity_contents_generation');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE activity_contents ADD CONSTRAINT chk_activity_contents_activity_xor_sector
            CHECK ((activity_id IS NOT NULL)::int + (sector_id IS NOT NULL)::int = 1)
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_activity_contents_activity
            ON activity_contents (activity_id, COALESCE(country_id, 0), locale, section)
            WHERE activity_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_activity_contents_sector
            ON activity_contents (sector_id, COALESCE(country_id, 0), locale, section)
            WHERE sector_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_contents');
    }
};
