<?php

declare(strict_types=1);

namespace App\Domain\Company\Models;

use App\Domain\Billing\Models\ContactVisibilityEvent;
use App\Domain\Company\Enums\ContactSource;
use App\Domain\Company\Enums\ContactType;
use App\Domain\Company\Enums\ContactVisibility;
use Database\Factories\CompanyContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyContact extends Model
{
    /** @use HasFactory<CompanyContactFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'value',
        'is_monetized',
        'visibility',
        'source',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'is_monetized' => 'boolean',
            'visibility' => ContactVisibility::class,
            'source' => ContactSource::class,
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<ContactVisibilityEvent, $this> */
    public function visibilityEvents(): HasMany
    {
        return $this->hasMany(ContactVisibilityEvent::class, 'contact_id');
    }
}
