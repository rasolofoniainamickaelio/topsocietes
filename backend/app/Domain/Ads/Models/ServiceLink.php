<?php

declare(strict_types=1);

namespace App\Domain\Ads\Models;

use App\Domain\Geo\Models\Country;
use Database\Factories\ServiceLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLink extends Model
{
    /** @use HasFactory<ServiceLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'group',
        'label',
        'url',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
