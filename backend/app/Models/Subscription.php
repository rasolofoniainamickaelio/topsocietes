<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'plan_id',
        'status',
        'provider',
        'provider_subscription_id',
        'started_at',
        'current_period_end',
        'cancelled_at',
        'auto_renew',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'provider' => PaymentProvider::class,
            'started_at' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ContactVisibilityEvent, $this> */
    public function visibilityEvents(): HasMany
    {
        return $this->hasMany(ContactVisibilityEvent::class);
    }
}
