<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenu mutualisé au niveau région/département — même forme que
 * `city_contents`/`district_contents` (Phase 08). Sert de niveau de repli
 * quand une commune ou un quartier n'a pas de contenu propre pour une
 * section donnée (commune → département → région, `AdminDivision::parent`
 * remonte la chaîne).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_division_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_division_id')->constrained()->restrictOnDelete();
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

            $table->unique(['admin_division_id', 'locale', 'section'], 'uq_admin_division_contents_division_locale_section');
            $table->index('generation_id', 'idx_admin_division_contents_generation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_division_contents');
    }
};
