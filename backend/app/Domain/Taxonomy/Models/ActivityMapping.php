<?php

declare(strict_types=1);

namespace App\Domain\Taxonomy\Models;

use Database\Factories\ActivityMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correspondance entre activités de nomenclatures différentes
 * (NAF ↔ NACEBEL ↔ SCIAN), pour mutualiser les contenus sectoriels entre
 * pays sans dupliquer les classifications nationales.
 */
class ActivityMapping extends Model
{
    /** @use HasFactory<ActivityMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'from_activity_id',
        'to_activity_id',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    /** @return BelongsTo<Activity, $this> */
    public function fromActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'from_activity_id');
    }

    /** @return BelongsTo<Activity, $this> */
    public function toActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'to_activity_id');
    }
}
