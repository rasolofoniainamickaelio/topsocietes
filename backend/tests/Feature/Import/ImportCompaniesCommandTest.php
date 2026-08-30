<?php

declare(strict_types=1);

use App\Domain\Geo\Models\Country;
use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Jobs\ProcessImportBatchChunkJob;
use App\Domain\Import\Models\ImportBatch;
use App\Domain\Import\Models\ImportMapping;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('starts an import batch and queues the first chunk', function (): void {
    Storage::fake('local');
    Queue::fake();

    $country = Country::factory()->create(['subdomain' => 'fr']);
    ImportMapping::factory()->for($country)->create([
        'column_map' => ['siren' => 'national_id', 'denomination' => 'legal_name'],
        'is_default' => true,
    ]);

    Storage::disk('local')->put(
        'imports/test.csv',
        "siren,denomination\n123456789,Acme SAS\n987654321,Beta SARL\n",
    );

    $this->artisan('import:companies', ['path' => 'imports/test.csv', 'country' => 'fr'])
        ->assertExitCode(0);

    $batch = ImportBatch::query()->where('country_id', $country->id)->firstOrFail();

    expect($batch->total_rows)->toBe(2)
        ->and($batch->status)->toBe(ImportStatus::Pending);

    Queue::assertPushed(ProcessImportBatchChunkJob::class, fn ($job) => $job->batch->is($batch));
});

it('fails clearly when no default mapping exists for the country', function (): void {
    Storage::fake('local');

    Country::factory()->create(['subdomain' => 'fr']);
    Storage::disk('local')->put('imports/test.csv', "siren,denomination\n123456789,Acme SAS\n");

    $this->artisan('import:companies', ['path' => 'imports/test.csv', 'country' => 'fr'])
        ->assertExitCode(1);
});

it('fails when the country is unknown or inactive', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('imports/test.csv', "siren,denomination\n123456789,Acme SAS\n");

    $this->artisan('import:companies', ['path' => 'imports/test.csv', 'country' => 'zz'])
        ->assertExitCode(1);
});

it('records who triggered the import when --user is given', function (): void {
    Storage::fake('local');
    Queue::fake();

    $country = Country::factory()->create(['subdomain' => 'fr']);
    ImportMapping::factory()->for($country)->create([
        'column_map' => ['siren' => 'national_id', 'denomination' => 'legal_name'],
        'is_default' => true,
    ]);
    $user = User::factory()->create();

    Storage::disk('local')->put('imports/test.csv', "siren,denomination\n123456789,Acme SAS\n");

    $this->artisan('import:companies', ['path' => 'imports/test.csv', 'country' => 'fr', '--user' => (string) $user->id])
        ->assertExitCode(0);

    $batch = ImportBatch::query()->where('country_id', $country->id)->firstOrFail();
    expect($batch->triggered_by)->toBe($user->id);
});
