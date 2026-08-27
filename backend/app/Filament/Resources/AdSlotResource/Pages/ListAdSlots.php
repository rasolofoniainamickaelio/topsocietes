<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdSlotResource\Pages;

use App\Filament\Resources\AdSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdSlots extends ListRecords
{
    protected static string $resource = AdSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
