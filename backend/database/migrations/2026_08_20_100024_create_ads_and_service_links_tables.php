<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Emplacements et campagnes publicitaires + liens de service, plus le
 * circuit de contestation d'une information (`dispute_reports` /
 * `dispute_events`).
 *
 * `reporter_email` est basculée en `citext` après création (insensible à
 * la casse, extension activée en Phase 0/Domaine A). `ip_hash` : jamais
 * l'IP en clair, seulement son hash — anti-abus sans donnée personnelle
 * brute conservée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('device');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('slot_id')->constrained('ad_slots')->restrictOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('theme')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('impressions_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->timestamps();

            $table->index(['slot_id', 'is_active'], 'idx_ad_campaigns_slot_active');
        });

        Schema::create('service_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('group');
            $table->string('label');
            $table->string('url');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dispute_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('field');
            $table->text('current_value')->nullable();
            $table->text('proposed_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_phone')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('company_id', 'idx_dispute_reports_company');
        });

        DB::statement('ALTER TABLE dispute_reports ALTER COLUMN reporter_email TYPE citext');

        Schema::create('dispute_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('dispute_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('dispute_id', 'idx_dispute_events_dispute');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_events');
        Schema::dropIfExists('dispute_reports');
        Schema::dropIfExists('service_links');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_slots');
    }
};
