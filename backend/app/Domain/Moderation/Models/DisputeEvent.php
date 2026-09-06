<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Models;

use App\Models\User;
use Database\Factories\DisputeEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Historique d'une contestation. Pas de colonne `updated_at` : immuable. */
class DisputeEvent extends Model
{
    /** @use HasFactory<DisputeEventFactory> */
    use HasFactory;

    use SoftDeletes;

    public const UPDATED_AT = null;

    protected $fillable = [
        'dispute_id',
        'user_id',
        'action',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<DisputeReport, $this> */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(DisputeReport::class, 'dispute_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
