<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table du package spatie/laravel-activitylog, consolidée en une seule
 * migration (les 3 migrations stub du package sont fusionnées ici) pour
 * suivre la convention du projet : une table = une migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer');
            $table->jsonb('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            $table->index('log_name', 'idx_activity_log_log_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('activitylog.table_name', 'activity_log'));
    }
};
