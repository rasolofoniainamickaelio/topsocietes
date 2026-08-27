<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdSlotResource\Pages;

use App\Filament\Resources\AdSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdSlot extends EditRecord
{
    protected static string $resource = AdSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
