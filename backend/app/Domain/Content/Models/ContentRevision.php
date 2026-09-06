<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentStatus;
use Database\Factories\ContentRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Instantané immuable d'un contenu mutualisé juste avant qu'il ne soit
 * écrasé par une régénération (Phase 08).
 */
class ContentRevision extends Model
{
    /** @use HasFactory<ContentRevisionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'content_type',
        'content_id',
        'locale',
        'section',
        'title',
        'body',
        'data',
        'status',
        'generation_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => ContentStatus::class,
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<AiGenerationJob, $this> */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGenerationJob::class, 'generation_id');
    }
}
