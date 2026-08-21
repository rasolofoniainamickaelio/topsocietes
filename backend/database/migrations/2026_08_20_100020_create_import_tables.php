<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pipeline d'import par lots, reprenable. `import_mappings` est créée
 * avant `import_batches` (qui la référence) pour éviter toute référence
 * en avant. `checkpoint` (jsonb) porte l'offset de reprise après
 * interruption ; `import_errors` permet de ne relancer que les lignes en
 * échec, jamais tout le lot.
 *
 * Referme la dernière référence en avant : `companies.source_batch_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->jsonb('column_map');
            $table->jsonb('transformers')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('country_id', 'idx_import_mappings_country');
        });

        DB::statement('CREATE UNIQUE INDEX uq_import_mappings_country_default ON import_mappings (country_id) WHERE is_default');

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('filename');
            $table->string('format');
            $table->foreignId('mapping_id')->nullable()->constrained('import_mappings')->restrictOnDelete();
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->string('status');
            $table->jsonb('checkpoint')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->jsonb('options')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'status'], 'idx_import_batches_country_status');
        });

        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->integer('row_number');
            $table->jsonb('raw_row')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('retried_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'is_resolved'], 'idx_import_errors_batch_resolved');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('source_batch_id', 'fk_companies_source_batch')
                ->references('id')->on('import_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('fk_companies_source_batch');
        });

        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('import_mappings');
    }
};
