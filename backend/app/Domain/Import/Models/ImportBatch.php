<?php

declare(strict_types=1);

namespace App\Domain\Import\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Geo\Models\Country;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Enums\ImportStatus;
use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'filename',
        'format',
        'mapping_id',
        'total_rows',
        'processed_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'status',
        'checkpoint',
        'started_at',
        'finished_at',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'format' => ImportFormat::class,
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'skipped_count' => 'integer',
            'error_count' => 'integer',
            'checkpoint' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'options' => 'array',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<ImportMapping, $this> */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ImportMapping::class, 'mapping_id');
    }

    /** @return HasMany<ImportError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class, 'batch_id');
    }

    /** @return HasMany<Company, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'source_batch_id');
    }
}
