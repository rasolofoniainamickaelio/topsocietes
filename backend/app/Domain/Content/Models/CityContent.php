<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\City;
use Database\Factories\CityContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityContent extends Model
{
    /** @use HasFactory<CityContentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'city_id',
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

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
