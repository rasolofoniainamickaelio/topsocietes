<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table séparée de `companies`, cœur de la monétisation : les contacts ne
 * sont jamais présents dans `companies` afin (a) de ne jamais les exposer
 * par erreur dans un `select *`, (b) de gérer plusieurs valeurs par type,
 * (c) d'appliquer le masquage/démasquage automatique par un simple job sur
 * cette table (CLAUDE.md §6.3). `visibility` par défaut à `hidden` : un
 * contact monétisé est masqué tant qu'aucun abonnement actif ne le
 * démasque — cette valeur par défaut est la garantie structurelle.
 *
 * La sérialisation API (ne jamais renvoyer `value` sans abonnement actif)
 * est un sujet de couche Resource/Action, hors périmètre de cette session
 * schéma — à documenter et implémenter en Phase 7 (API publique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('value');
            $table->boolean('is_monetized')->default(true);
            $table->string('visibility')->default('hidden');
            $table->string('source');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('company_id', 'idx_company_contacts_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_contacts');
    }
};
