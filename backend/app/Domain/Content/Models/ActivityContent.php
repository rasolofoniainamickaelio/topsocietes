<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use Database\Factories\ActivityContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityContent extends Model
{
    /** @use HasFactory<ActivityContentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'sector_id',
        'country_id',
        'locale',
        'section',
        'title',
        'body',
        'data',
        'status',
        'quality_score',
        'generation_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => ContentStatus::class,
            'quality_score' => 'integer',
            'published_at' => 'datetime',
        ];
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

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
