<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifiants externes explicites (Phase 09, collecte de sources) :
 * sans eux, un connecteur devrait résoudre l'entité par recherche floue
 * sur le nom de la ville (ambigu — plusieurs "Lyon" existent dans le
 * monde), ce que l'anti-hallucination interdit. Une ville sans identifiant
 * renseigné est simplement ignorée par les connecteurs concernés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('wikidata_id')->nullable()->after('has_local_content');
            $table->string('wikipedia_title')->nullable()->after('wikidata_id');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['wikidata_id', 'wikipedia_title']);
        });
    }
};
