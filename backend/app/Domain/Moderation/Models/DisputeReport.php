<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Observers\DisputeReportObserver;
use App\Models\User;
use Database\Factories\DisputeReportFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** @property DisputeStatus $status */
#[ObservedBy(DisputeReportObserver::class)]
class DisputeReport extends Model
{
    /** @use HasFactory<DisputeReportFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'field',
        'current_value',
        'proposed_value',
        'reason',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'evidence_path',
        'status',
        'assigned_to',
        'internal_note',
        'resolved_at',
        'ip_hash',
        'anonymized_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'resolved_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<DisputeEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(DisputeEvent::class, 'dispute_id');
    }
}
