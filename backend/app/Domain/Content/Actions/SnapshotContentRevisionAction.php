<?php

declare(strict_types=1);

namespace App\Domain\Content\Actions;

use App\Domain\Content\Models\ContentRevision;
use Illuminate\Database\Eloquent\Model;

/**
 * Appelée juste avant d'écraser une ligne de contenu mutualisé existante
 * (jamais sur une création) — garde une trace de ce qu'elle contenait avant
 * la régénération (Phase 08).
 */
class SnapshotContentRevisionAction
{
    public function execute(Model $content): void
    {
        ContentRevision::query()->create([
            'content_type' => $content->getMorphClass(),
            'content_id' => $content->getKey(),
            'locale' => $content->getAttribute('locale'),
            'section' => $content->getAttribute('section'),
            'title' => $content->getAttribute('title'),
            'body' => $content->getAttribute('body'),
            'data' => $content->getAttribute('data'),
            'status' => $content->getAttribute('status'),
            'generation_id' => $content->getAttribute('generation_id'),
        ]);
    }
}
