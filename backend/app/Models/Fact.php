<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Faits structurés sourcés — c'est cette table qui alimente le JSON envoyé
 * au LLM. `is_usable` (colonne générée) doit être le seul filtre que le
 * générateur applique pour sélectionner ses faits (CLAUDE.md §6.7).
 */
class Fact extends Model
{
    /** @use HasFactory<FactFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'key',
        'value',
        'value_json',
        'source_id',
        'source_url',
        'date_retrieved',
        'confidence_score',
        'corroboration_count',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
            'date_retrieved' => 'datetime',
            'confidence_score' => 'integer',
            'corroboration_count' => 'integer',
            'last_verified_at' => 'datetime',
            'is_usable' => 'boolean',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return HasMany<ContentSourceLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(ContentSourceLink::class);
    }

    /**
     * @param  Builder<Fact>  $query
     * @return Builder<Fact>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_usable', true);
    }
}
