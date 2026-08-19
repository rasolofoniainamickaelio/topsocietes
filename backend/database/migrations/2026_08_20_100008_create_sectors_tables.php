<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `sectors` : regroupement éditorial transversal (« Transports et
 * entreposage »), indépendant des codes nationaux — c'est ce qui permet de
 * générer un contenu métier unique réutilisé par la France, la Belgique et
 * le Québec malgré des nomenclatures différentes.
 *
 * `activity_sector` rattache les activités de chaque nomenclature à ces
 * secteurs. `activity_mappings` fait la correspondance directe entre
 * activités de nomenclatures différentes (NAF ↔ NACEBEL ↔ SCIAN).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('companies_count')->default(0);
            $table->timestamp('counts_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->restrictOnDelete();
            $table->foreignId('sector_id')->constrained('sectors')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'sector_id'], 'uq_activity_sector');
        });

        Schema::create('activity_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_activity_id')->constrained('activities')->restrictOnDelete();
            $table->foreignId('to_activity_id')->constrained('activities')->restrictOnDelete();
            $table->smallInteger('confidence');
            $table->timestamps();

            $table->unique(['from_activity_id', 'to_activity_id'], 'uq_activity_mappings_from_to');
        });

        DB::statement('ALTER TABLE activity_mappings ADD CONSTRAINT chk_activity_mappings_confidence CHECK (confidence BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_mappings');
        Schema::dropIfExists('activity_sector');
        Schema::dropIfExists('sectors');
    }
};
