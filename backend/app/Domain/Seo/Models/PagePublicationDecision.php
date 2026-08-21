<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use App\Domain\Seo\Enums\PublicationDecision;
use Database\Factories\PagePublicationDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagePublicationDecision extends Model
{
    /** @use HasFactory<PagePublicationDecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'route_id',
        'score',
        'decision',
        'reasons',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'decision' => PublicationDecision::class,
            'reasons' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PageRoute, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(PageRoute::class, 'route_id');
    }
}
