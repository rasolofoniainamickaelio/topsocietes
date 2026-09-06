<?php

declare(strict_types=1);

use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\AdminDivision;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Import\Actions\ProcessImportChunkAction;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportError;
use App\Domain\Import\Models\ImportMapping;
use Illuminate\Support\Facades\Storage;

function makeBatch(Country $country, ImportMapping $mapping, string $csv, array $options = []): ImportBatch
{
    $path = 'imports/'.uniqid('test_', true).'.csv';
    Storage::disk('local')->put($path, $csv);

    $lines = max(substr_count($csv, "\n") - 1, 0);

    return ImportBatch::factory()->for($country)->create([
        'filename' => $path,
        'mapping_id' => $mapping->id,
        'total_rows' => $lines,
        'checkpoint' => ['offset' => 0],
        'options' => $options,
    ]);
}

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

it('creates companies, resolves the city by postal code, and logs a missing-field error', function (): void {
    $csv = "siren,denomination,cp\n"
        ."111111111,Acme SAS,75001\n"
        .",No Name,75001\n"; // ligne invalide : national_id manquant

    $batch = makeBatch($this->country, $this->mapping, $csv);

    $hasMore = app(ProcessImportChunkAction::class)->execute($batch);

    $batch->refresh();

    expect($hasMore)->toBeFalse()
        ->and($batch->status)->toBe(ImportStatus::Completed)
        ->and($batch->created_count)->toBe(1)
        ->and($batch->error_count)->toBe(1);

    $company = Company::query()->where('national_id', '111111111')->firstOrFail();
    expect($company->city_id)->toBe($this->city->id)
        ->and($company->admin_division_id)->toBe($this->adminDivision->id)
        ->and($company->geocoding_status)->toBe(GeocodingStatus::CityLevel);

    $error = ImportError::query()->where('batch_id', $batch->id)->firstOrFail();
    expect($error->error_code)->toBe('missing_required_field')
        ->and($error->row_number)->toBe(2)
        ->and($error->error_message)->toBe('Champ(s) requis manquant(s) : national_id');
});

it('updates a changed company and skips an unchanged one on re-import', function (): void {
    $initialCsv = "siren,denomination,cp\n"
        ."222222222,Acme SAS,75001\n"
        ."333333333,Beta SARL,75001\n";

    $firstBatch = makeBatch($this->country, $this->mapping, $initialCsv);
    app(ProcessImportChunkAction::class)->execute($firstBatch);

    $secondCsv = "siren,denomination,cp\n"
        ."222222222,Acme Group,75001\n" // nom modifié -> update
        ."333333333,Beta SARL,75001\n";  // inchangé -> skip

    $secondBatch = makeBatch($this->country, $this->mapping, $secondCsv);
    app(ProcessImportChunkAction::class)->execute($secondBatch);

    $secondBatch->refresh();

    expect($secondBatch->updated_count)->toBe(1)
        ->and($secondBatch->skipped_count)->toBe(1)
        ->and($secondBatch->created_count)->toBe(0);

    expect(Company::query()->where('national_id', '222222222')->firstOrFail()->legal_name)->toBe('Acme Group');
});

it('does not regress an existing city/admin_division when a later import has no resolvable postal code', function (): void {
    $firstCsv = "siren,denomination,cp\n888888888,Acme SAS,75001\n";
    $firstBatch = makeBatch($this->country, $this->mapping, $firstCsv);
    app(ProcessImportChunkAction::class)->execute($firstBatch);

    $company = Company::query()->where('national_id', '888888888')->firstOrFail();
    expect($company->city_id)->toBe($this->city->id);

    // Nouvelle ligne : même entreprise, code postal absent/inconnu cette fois
    $secondCsv = "siren,denomination,cp\n888888888,Acme SAS Group,99999\n";
    $secondBatch = makeBatch($this->country, $this->mapping, $secondCsv);
    app(ProcessImportChunkAction::class)->execute($secondBatch);

    $company->refresh();
    expect($company->legal_name)->toBe('Acme SAS Group')
        ->and($company->city_id)->toBe($this->city->id)
        ->and($company->admin_division_id)->toBe($this->adminDivision->id);
});

it('advances the checkpoint across multiple chunks and resumes correctly', function (): void {
    $csv = "siren,denomination,cp\n"
        ."444444444,A,75001\n"
        ."555555555,B,75001\n"
        ."666666666,C,75001\n";

    $batch = makeBatch($this->country, $this->mapping, $csv, ['chunk_size' => 2]);

    $hasMoreAfterFirst = app(ProcessImportChunkAction::class)->execute($batch->fresh());
    $batch->refresh();

    expect($hasMoreAfterFirst)->toBeTrue()
        ->and($batch->checkpoint['offset'])->toBe(2)
        ->and($batch->processed_rows)->toBe(2)
        ->and($batch->status)->toBe(ImportStatus::Running);

    $hasMoreAfterSecond = app(ProcessImportChunkAction::class)->execute($batch->fresh());
    $batch->refresh();

    expect($hasMoreAfterSecond)->toBeFalse()
        ->and($batch->checkpoint['offset'])->toBe(3)
        ->and($batch->processed_rows)->toBe(3)
        ->and($batch->status)->toBe(ImportStatus::Completed)
        ->and($batch->created_count)->toBe(3);
});

it('reads rows from an XML file', function (): void {
    $xml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <companies>
            <company>
                <siren>777777777</siren>
                <denomination>Gamma XML</denomination>
                <cp>75001</cp>
            </company>
        </companies>
        XML;

    $path = 'imports/'.uniqid('test_', true).'.xml';
    Storage::disk('local')->put($path, $xml);

    $batch = ImportBatch::factory()->for($this->country)->create([
        'filename' => $path,
        'format' => ImportFormat::Xml,
        'mapping_id' => $this->mapping->id,
        'total_rows' => 1,
        'checkpoint' => ['offset' => 0],
    ]);

    app(ProcessImportChunkAction::class)->execute($batch);

    $batch->refresh();
    expect($batch->created_count)->toBe(1)
        ->and($batch->status)->toBe(ImportStatus::Completed);

    expect(Company::query()->where('national_id', '777777777')->firstOrFail()->legal_name)->toBe('Gamma XML');
});

it('reads rows from a JSON Lines file', function (): void {
    $jsonl = implode("\n", [
        json_encode(['siren' => '888800001', 'denomination' => 'Delta JSON', 'cp' => '75001']),
        json_encode(['siren' => '888800002', 'denomination' => 'Epsilon JSON', 'cp' => '75001']),
    ]);

    $path = 'imports/'.uniqid('test_', true).'.jsonl';
    Storage::disk('local')->put($path, $jsonl);

    $batch = ImportBatch::factory()->for($this->country)->create([
        'filename' => $path,
        'format' => ImportFormat::Json,
        'mapping_id' => $this->mapping->id,
        'total_rows' => 2,
        'checkpoint' => ['offset' => 0],
    ]);

    app(ProcessImportChunkAction::class)->execute($batch);

    $batch->refresh();
    expect($batch->created_count)->toBe(2)
        ->and($batch->status)->toBe(ImportStatus::Completed);

    expect(Company::query()->where('national_id', '888800001')->firstOrFail()->legal_name)->toBe('Delta JSON');
    expect(Company::query()->where('national_id', '888800002')->firstOrFail()->legal_name)->toBe('Epsilon JSON');
});

it('counts an intra-batch duplicate national_id separately from updated/skipped', function (): void {
    $csv = "siren,denomination,cp\n"
        ."999999991,First Row,75001\n"
        ."999999991,First Row Renamed,75001\n"; // même siren, même fichier

    $batch = makeBatch($this->country, $this->mapping, $csv);

    app(ProcessImportChunkAction::class)->execute($batch);

    $batch->refresh();
    expect($batch->created_count)->toBe(1)
        ->and($batch->updated_count)->toBe(1)
        ->and($batch->duplicate_count)->toBe(1);

    expect(Company::query()->where('national_id', '999999991')->firstOrFail()->legal_name)->toBe('First Row Renamed');
});

it('geocodes exactly when the source file provides coordinates, and marks an unresolvable row as failed', function (): void {
    $mappingWithCoords = ImportMapping::factory()->for($this->country)->create([
        'column_map' => [
            'siren' => 'national_id',
            'denomination' => 'legal_name',
            'cp' => 'postal_code',
            'lat' => 'latitude',
            'lng' => 'longitude',
        ],
    ]);

    $csv = "siren,denomination,cp,lat,lng\n"
        ."100000001,Exact Co,75001,48.8566,2.3522\n" // coordonnées exactes fournies
        ."100000002,Failed Co,00000,,\n";             // code postal inconnu, pas de coordonnées

    $batch = makeBatch($this->country, $mappingWithCoords, $csv);

    app(ProcessImportChunkAction::class)->execute($batch);

    $exact = Company::query()->where('national_id', '100000001')->firstOrFail();
    expect($exact->geocoding_status)->toBe(GeocodingStatus::Exact);

    $failed = Company::query()->where('national_id', '100000002')->firstOrFail();
    expect($failed->geocoding_status)->toBe(GeocodingStatus::Failed);
});

it('never downgrades an already-exact geocoding on a later, less precise re-import', function (): void {
    $mappingWithCoords = ImportMapping::factory()->for($this->country)->create([
        'column_map' => [
            'siren' => 'national_id',
            'denomination' => 'legal_name',
            'cp' => 'postal_code',
            'lat' => 'latitude',
            'lng' => 'longitude',
        ],
    ]);

    $firstCsv = "siren,denomination,cp,lat,lng\n100000003,Precise Co,75001,48.8566,2.3522\n";
    $firstBatch = makeBatch($this->country, $mappingWithCoords, $firstCsv);
    app(ProcessImportChunkAction::class)->execute($firstBatch);

    $secondCsv = "siren,denomination,cp,lat,lng\n100000003,Precise Co Renamed,75001,,\n";
    $secondBatch = makeBatch($this->country, $mappingWithCoords, $secondCsv);
    app(ProcessImportChunkAction::class)->execute($secondBatch);

    $company = Company::query()->where('national_id', '100000003')->firstOrFail();
    expect($company->legal_name)->toBe('Precise Co Renamed')
        ->and($company->geocoding_status)->toBe(GeocodingStatus::Exact);
});
