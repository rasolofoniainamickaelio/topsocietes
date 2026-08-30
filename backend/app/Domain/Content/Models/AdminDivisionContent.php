<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Geo\Models\AdminDivision;
use Database\Factories\AdminDivisionContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminDivisionContent extends Model
{
    /** @use HasFactory<AdminDivisionContentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'admin_division_id',
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

    /** @return BelongsTo<AdminDivision, $this> */
    public function adminDivision(): BelongsTo
    {
        return $this->belongsTo(AdminDivision::class);
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
