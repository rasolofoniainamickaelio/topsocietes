<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'nomenclature_id',
        'parent_id',
        'level',
        'code',
        'label',
        'public_label',
        'slug',
        'is_publishable',
        'companies_count',
        'counts_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_publishable' => 'boolean',
            'companies_count' => 'integer',
            'counts_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ActivityNomenclature, $this> */
    public function nomenclature(): BelongsTo
    {
        return $this->belongsTo(ActivityNomenclature::class, 'nomenclature_id');
    }

    /** @return BelongsTo<Activity, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Activity, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsToMany<Sector, $this> */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'activity_sector');
    }

    /** @return HasMany<ActivityMapping, $this> */
    public function outgoingMappings(): HasMany
    {
        return $this->hasMany(ActivityMapping::class, 'from_activity_id');
    }

    /** @return HasMany<ActivityMapping, $this> */
    public function incomingMappings(): HasMany
    {
        return $this->hasMany(ActivityMapping::class, 'to_activity_id');
    }

    /** @return HasMany<ActivityContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(ActivityContent::class);
    }

    /** @return HasMany<CityActivityContent, $this> */
    public function cityActivityContents(): HasMany
    {
        return $this->hasMany(CityActivityContent::class);
    }

    /** @return MorphMany<Fact, $this> */
    public function facts(): MorphMany
    {
        return $this->morphMany(Fact::class, 'subject');
    }

    /** @return MorphMany<SourceDocument, $this> */
    public function sourceDocuments(): MorphMany
    {
        return $this->morphMany(SourceDocument::class, 'subject');
    }

    /** @return MorphMany<AiGenerationJob, $this> */
    public function generationJobs(): MorphMany
    {
        return $this->morphMany(AiGenerationJob::class, 'target');
    }
}
