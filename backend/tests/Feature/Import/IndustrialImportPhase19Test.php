<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Import\Actions\ProcessImportChunkAction;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Jobs\ProcessImportBatchChunkJob;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportMapping;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('respects --limit and --dry-run without writing companies', function (): void {
    Storage::fake('local');
    Queue::fake();

    $country = Country::factory()->create(['subdomain' => 'fr']);
    AdminDivision::factory()->for($country)->create();
    ImportMapping::factory()->for($country)->create([
        'column_map' => ['siren' => 'national_id', 'denomination' => 'legal_name'],
        'is_default' => true,
    ]);

    Storage::disk('local')->put(
        'imports/test.csv',
        "siren,denomination\n111,One\n222,Two\n333,Three\n",
    );

    $this->artisan('import:companies', [
        'path' => 'imports/test.csv',
        'country' => 'fr',
        '--limit' => '2',
        '--dry-run' => true,
    ])->assertExitCode(0);

    $batch = ImportBatch::query()->where('country_id', $country->id)->firstOrFail();

    expect($batch->total_rows)->toBe(2)
        ->and($batch->options['dry_run'])->toBeTrue()
        ->and($batch->options['limit'])->toBe(2);

    Queue::assertPushed(ProcessImportBatchChunkJob::class, function (ProcessImportBatchChunkJob $job) use ($batch): bool {
        return $job->batch->is($batch) && $job->queue === 'imports';
    });

    // Exécuter le chunk synchronement pour valider le dry-run.
    $job = new ProcessImportBatchChunkJob($batch);
    $job->handle(app(ProcessImportChunkAction::class));

    $batch->refresh();

    expect($batch->status)->toBe(ImportStatus::Completed)
        ->and($batch->created_count)->toBe(2)
        ->and(Company::query()->count())->toBe(0);
});

it('writes a bounded csv sample to local storage', function (): void {
    Storage::fake('local');

    $source = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phase19-source.csv';
    file_put_contents($source, "a,b\n1,2\n3,4\n5,6\n7,8\n");

    $this->artisan('import:prepare-sample', [
        'source' => $source,
        '--limit' => '2',
        '--output' => 'imports/samples/test-sample.csv',
    ])->assertExitCode(0);

    $content = Storage::disk('local')->get('imports/samples/test-sample.csv');
    expect(substr_count($content, "\n"))->toBe(3); // header + 2 rows (+ possible trailing)

    @unlink($source);
});

it('prints a structured import report', function (): void {
    $country = Country::factory()->create();
    $batch = ImportBatch::factory()->for($country)->create([
        'processed_rows' => 100,
        'created_count' => 80,
        'error_count' => 5,
        'status' => ImportStatus::Completed,
        'started_at' => now()->subMinutes(2),
        'finished_at' => now(),
        'options' => [
            'city_resolved_count' => 70,
            'activity_resolved_count' => 60,
            'error_codes' => ['missing_required_field' => 5],
        ],
    ]);

    $this->artisan('import:report', ['batch' => (string) $batch->id])
        ->assertExitCode(0)
        ->expectsOutputToContain("Rapport lot #{$batch->id}");
});
