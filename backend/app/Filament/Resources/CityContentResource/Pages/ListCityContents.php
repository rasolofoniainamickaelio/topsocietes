<?php

declare(strict_types=1);

namespace App\Filament\Resources\CityContentResource\Pages;

use App\Filament\Resources\CityContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityContents extends ListRecords
{
    protected static string $resource = CityContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
