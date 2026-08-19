<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContentSourceLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot entre un contenu publié (`content`) et les faits/documents source
 * utilisés pour le générer — permet le contrôle a posteriori de chaque
 * affirmation publiée (CLAUDE.md §6.7).
 */
class ContentSourceLink extends Model
{
    /** @use HasFactory<ContentSourceLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'content_type',
        'content_id',
        'fact_id',
        'source_document_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Fact, $this> */
    public function fact(): BelongsTo
    {
        return $this->belongsTo(Fact::class);
    }

    /** @return BelongsTo<SourceDocument, $this> */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class);
    }
}
