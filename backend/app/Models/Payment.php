<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aucune donnée bancaire stockée : uniquement une référence du
 * prestataire (`provider_payment_id`) et son `payload` brut.
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'provider',
        'provider_payment_id',
        'amount_cents',
        'currency',
        'status',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
