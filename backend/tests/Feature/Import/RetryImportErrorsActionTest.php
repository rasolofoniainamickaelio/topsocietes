<?php

declare(strict_types=1);

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Import\Actions\ProcessImportChunkAction;
use App\Domain\Import\Actions\RetryImportErrorsAction;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportError;
use App\Domain\Import\Models\ImportMapping;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->country = Country::factory()->create(['subdomain' => 'fr']);
    $this->adminDivision = AdminDivision::factory()->for($this->country)->create();
    $this->city = City::factory()->for($this->country)->create([
        'postal_codes' => ['75001'],
        'admin_division_id' => $this->adminDivision->id,
    ]);
    $this->mapping = ImportMapping::factory()->for($this->country)->create([
        'column_map' => [
            'siren' => 'national_id',
            'denomination' => 'legal_name',
            'cp' => 'postal_code',
        ],
        'is_default' => true,
    ]);
});

it('reprocesses only unresolved errors and leaves resolved ones untouched', function (): void {
    // Une ligne invalide (national_id manquant) déclenche une ImportError.
    $csv = "siren,denomination,cp\n,No Name,75001\n";
    $path = 'imports/'.uniqid('test_', true).'.csv';
    Storage::disk('local')->put($path, $csv);

    $batch = ImportBatch::factory()->for($this->country)->create([
        'filename' => $path,
        'mapping_id' => $this->mapping->id,
        'total_rows' => 1,
        'checkpoint' => ['offset' => 0],
    ]);

    app(ProcessImportChunkAction::class)->execute($batch);
    $batch->refresh();

    expect($batch->error_count)->toBe(1);

    $error = ImportError::query()->where('batch_id', $batch->id)->firstOrFail();

    // La correction n'existe que dans `raw_row` : on simule une correction
    // manuelle (ex. via le back-office) avant de relancer.
    $error->update(['raw_row' => ['siren' => '123456789', 'denomination' => 'Fixed SAS', 'cp' => '75001']]);

    $result = app(RetryImportErrorsAction::class)->execute($batch);

    expect($result)->toBe(['resolved' => 1, 'still_failing' => 0]);

    $batch->refresh();
    expect($batch->error_count)->toBe(0)
        ->and($batch->created_count)->toBe(1);

    expect($error->refresh()->is_resolved)->toBeTrue();
    expect(Company::query()->where('national_id', '123456789')->firstOrFail()->legal_name)->toBe('Fixed SAS');
});

it('keeps a still-invalid row unresolved and reports it as still failing', function (): void {
    $csv = "siren,denomination,cp\n,No Name,75001\n";
    $path = 'imports/'.uniqid('test_', true).'.csv';
    Storage::disk('local')->put($path, $csv);

    $batch = ImportBatch::factory()->for($this->country)->create([
        'filename' => $path,
        'mapping_id' => $this->mapping->id,
        'total_rows' => 1,
        'checkpoint' => ['offset' => 0],
    ]);

    app(ProcessImportChunkAction::class)->execute($batch);
    $batch->refresh();

    $result = app(RetryImportErrorsAction::class)->execute($batch);

    expect($result)->toBe(['resolved' => 0, 'still_failing' => 1]);

    $batch->refresh();
    expect($batch->error_count)->toBe(1);

    $error = ImportError::query()->where('batch_id', $batch->id)->firstOrFail();
    expect($error->is_resolved)->toBeFalse()
        ->and($error->retried_at)->not->toBeNull();
});
