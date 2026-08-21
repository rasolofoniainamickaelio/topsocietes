<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\City;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Database\Factories\CityActivityContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityActivityContent extends Model
{
    /** @use HasFactory<CityActivityContentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'city_id',
        'activity_id',
        'sector_id',
        'locale',
        'section',
        'body',
        'data',
        'status',
        'companies_count_at_generation',
        'generation_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => ContentStatus::class,
            'companies_count_at_generation' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
