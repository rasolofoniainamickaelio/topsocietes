<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le seul contenu réellement croisé (ville × activité/secteur). Même
 * logique XOR + unicité par index partiels que `activity_contents` — le
 * prompt donne `unique(city_id, activity_id, locale, section)` mais
 * `activity_id` est nullable (branche sector du XOR) : un simple UNIQUE
 * composite laisserait passer des doublons côté secteur (NULL ≠ NULL en
 * SQL), d'où les deux index partiels ci-dessous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_activity_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->restrictOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->restrictOnDelete();
            $table->string('locale', 10);
            $table->string('section');
            $table->text('body')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('status');
            $table->integer('companies_count_at_generation')->nullable();
            $table->unsignedBigInteger('generation_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('generation_id', 'idx_city_activity_contents_generation');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE city_activity_contents ADD CONSTRAINT chk_city_activity_contents_activity_xor_sector
            CHECK ((activity_id IS NOT NULL)::int + (sector_id IS NOT NULL)::int = 1)
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_city_activity_contents_activity
            ON city_activity_contents (city_id, activity_id, locale, section)
            WHERE activity_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_city_activity_contents_sector
            ON city_activity_contents (city_id, sector_id, locale, section)
            WHERE sector_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('city_activity_contents');
    }
};
