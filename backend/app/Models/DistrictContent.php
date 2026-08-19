<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\DistrictContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistrictContent extends Model
{
    /** @use HasFactory<DistrictContentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'district_id',
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

    /** @return BelongsTo<District, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
