<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\ContactVisibilityAction;
use App\Domain\Billing\Enums\ContactVisibilityTrigger;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use App\Models\User;
use Database\Factories\ContactVisibilityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace le cycle complet : non abonné → masqué → paiement → visible →
 * expiration → masqué. Pas de colonne `updated_at` : un événement est
 * immuable.
 */
class ContactVisibilityEvent extends Model
{
    /** @use HasFactory<ContactVisibilityEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'contact_id',
        'action',
        'triggered_by',
        'subscription_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'action' => ContactVisibilityAction::class,
            'triggered_by' => ContactVisibilityTrigger::class,
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<CompanyContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(CompanyContact::class, 'contact_id');
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
