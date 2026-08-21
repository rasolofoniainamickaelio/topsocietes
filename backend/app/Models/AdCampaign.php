<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaign extends Model
{
    /** @use HasFactory<AdCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'slot_id',
        'title',
        'body',
        'cta_label',
        'cta_url',
        'theme',
        'starts_at',
        'ends_at',
        'weight',
        'is_active',
        'impressions_count',
        'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'weight' => 'integer',
            'is_active' => 'boolean',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<AdSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AdSlot::class, 'slot_id');
    }
}
