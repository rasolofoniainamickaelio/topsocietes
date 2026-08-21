<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Database\Factories\SourceDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Donnée brute (`raw_payload`) ET normalisée (`normalized_payload`)
 * conservées pour une source donnée. `subject` : city, district, poi ou
 * activity (jamais company — voir `facts` pour les sujets entreprise).
 */
class SourceDocument extends Model
{
    /** @use HasFactory<SourceDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'source_id',
        'subject_type',
        'subject_id',
        'url',
        'raw_payload',
        'normalized_payload',
        'fetched_at',
        'hash',
        'http_status',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'normalized_payload' => 'array',
            'fetched_at' => 'datetime',
            'http_status' => 'integer',
        ];
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<ContentSourceLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(ContentSourceLink::class);
    }
}
