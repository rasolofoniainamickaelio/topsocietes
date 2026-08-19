<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenus mutualisés au niveau ville et quartier. `title`/`body` restent
 * nullable : certaines sections portent leur contenu dans `data` (jsonb
 * structuré : chronologies, listes, statistiques) plutôt qu'en prose.
 * Règle métier (à respecter par le pipeline, pas exprimable en contrainte
 * SQL) : un contenu de quartier n'est jamais une reformulation du contenu
 * de la commune ; sans donnée spécifique, la ligne n'est pas créée — pas
 * de ligne vide, pas de texte de remplissage.
 *
 * `generation_id` référence `ai_generation_jobs` (Domaine G, pas encore
 * créée) : colonne posée sans contrainte FK ici, contrainte ajoutée dans
 * la migration `create_ai_pipeline_tables`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
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

            $table->unique(['city_id', 'locale', 'section'], 'uq_city_contents_city_locale_section');
            $table->index('generation_id', 'idx_city_contents_generation');
        });

        Schema::create('district_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
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

            $table->unique(['district_id', 'locale', 'section'], 'uq_district_contents_district_locale_section');
            $table->index('generation_id', 'idx_district_contents_generation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_contents');
        Schema::dropIfExists('city_contents');
    }
};
