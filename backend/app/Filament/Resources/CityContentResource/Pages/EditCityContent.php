<?php

declare(strict_types=1);

namespace App\Filament\Resources\CityContentResource\Pages;

use App\Filament\Resources\CityContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCityContent extends EditRecord
{
    protected static string $resource = CityContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
