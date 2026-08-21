<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table de référence — pas d'enum figé en base pour les clés de section :
 * chaque bloc éditorial est activable/désactivable et priorisable sans
 * toucher au code (`is_enabled`, `display_order`), et `min_facts_required`
 * permet au pipeline IA de refuser une génération si trop peu de faits
 * sourcés existent pour ce bloc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('scope');
            $table->boolean('is_enabled')->default(true);
            $table->smallInteger('min_facts_required')->default(0);
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_sections');
    }
};
