<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Ads\Enums\AdDevice;
use App\Domain\Ads\Models\AdSlot;
use Illuminate\Database\Seeder;

/**
 * 2 emplacements desktop (rail droit sticky, CLAUDE.md §5.4) + 2
 * emplacements mobiles repositionnés après les blocs Contact et Quartier
 * plutôt que dans un rail, puisque mobile n'a pas de colonne latérale.
 */
class AdSlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['code' => 'rail-desktop-1', 'label' => 'Rail droit — position 1', 'device' => AdDevice::Desktop->value, 'position' => 1],
            ['code' => 'rail-desktop-2', 'label' => 'Rail droit — position 2', 'device' => AdDevice::Desktop->value, 'position' => 2],
            ['code' => 'mobile-after-contact', 'label' => 'Mobile — après le bloc Contact', 'device' => AdDevice::Mobile->value, 'position' => 1],
            ['code' => 'mobile-after-district', 'label' => 'Mobile — après le bloc Quartier', 'device' => AdDevice::Mobile->value, 'position' => 2],
        ];

        foreach ($slots as $slot) {
            AdSlot::query()->updateOrCreate(
                ['code' => $slot['code']],
                ['label' => $slot['label'], 'device' => $slot['device'], 'position' => $slot['position'], 'is_active' => true]
            );
        }
    }
}
