<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\RedirectReason;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'country_id',
        'from_path',
        'to_path',
        'status_code',
        'reason',
        'hit_count',
        'last_hit_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'reason' => RedirectReason::class,
            'hit_count' => 'integer',
            'last_hit_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
