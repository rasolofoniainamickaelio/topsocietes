<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ImportMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportMapping extends Model
{
    /** @use HasFactory<ImportMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'column_map',
        'transformers',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'transformers' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<ImportBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(ImportBatch::class, 'mapping_id');
    }
}
