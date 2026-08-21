<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `activity_nomenclatures` référence les classifications nationales (NAF,
 * NACEBEL, SCIAN…) ; `activities` est une table unique hiérarchique et
 * auto-référencée (section/division/groupe/classe/sous-classe), commune à
 * toutes les nomenclatures plutôt qu'une table par pays.
 *
 * FK vers le référentiel de nomenclature en `restrict` par défaut (§5.6) :
 * on ne supprime jamais une nomenclature ou une activité sous laquelle des
 * entreprises ou du contenu existent encore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_nomenclatures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('version')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomenclature_id')->constrained('activity_nomenclatures')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('activities')->restrictOnDelete();
            $table->smallInteger('level');
            $table->string('code');
            $table->text('label');
            $table->string('public_label')->nullable();
            $table->string('slug');
            $table->boolean('is_publishable')->default(true);
            $table->integer('companies_count')->default(0);
            $table->timestamp('counts_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['nomenclature_id', 'code'], 'uq_activities_nomenclature_code');
            $table->unique(['nomenclature_id', 'slug'], 'uq_activities_nomenclature_slug');
            $table->index('parent_id', 'idx_activities_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_nomenclatures');
    }
};
