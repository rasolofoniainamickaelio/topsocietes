<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdDevice;
use Database\Factories\AdSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSlot extends Model
{
    /** @use HasFactory<AdSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'device',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'device' => AdDevice::class,
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AdCampaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'slot_id');
    }
}
