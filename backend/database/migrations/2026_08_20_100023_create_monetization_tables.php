<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revendication de fiche, abonnements et paiements — le cœur du modèle
 * économique (masquage/démasquage des contacts, CLAUDE.md §6.3).
 *
 * `idx_subscriptions_status_period_end` est l'index déterminant pour
 * l'automatisation : c'est lui qui permet au job de remasquage de trouver
 * en O(log n) les abonnements expirés parmi des millions de lignes.
 *
 * `payments` ne stocke jamais de donnée bancaire : uniquement des
 * références du prestataire (`provider_payment_id`) et le `payload` brut
 * qu'il renvoie (déjà expurgé de toute donnée sensible côté prestataire).
 *
 * `contact_visibility_events` trace le cycle complet : non abonné →
 * masqué → paiement → visible → expiration → masqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->string('verification_method');
            $table->string('evidence_path')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id', 'idx_company_claims_company');
        });

        // Une seule revendication approuvée par entreprise.
        DB::statement("CREATE UNIQUE INDEX uq_company_claims_company_approved ON company_claims (company_id) WHERE status = 'approved'");

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('country_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('price_cents');
            $table->char('currency', 3);
            $table->string('billing_period');
            $table->jsonb('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->string('provider');
            $table->string('provider_subscription_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['status', 'current_period_end'], 'idx_subscriptions_status_period_end');
            $table->index('company_id', 'idx_subscriptions_company');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->integer('amount_cents');
            $table->char('currency', 3);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamps();

            $table->index('subscription_id', 'idx_payments_subscription');
        });

        Schema::create('contact_visibility_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('company_contacts')->nullOnDelete();
            $table->string('action');
            $table->string('triggered_by');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at'], 'idx_contact_visibility_events_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_visibility_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('company_claims');
    }
};
