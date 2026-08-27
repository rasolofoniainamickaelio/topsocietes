<?php

declare(strict_types=1);

namespace App\Filament\Resources\DistrictContentResource\Pages;

use App\Filament\Resources\DistrictContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDistrictContent extends EditRecord
{
    protected static string $resource = DistrictContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
