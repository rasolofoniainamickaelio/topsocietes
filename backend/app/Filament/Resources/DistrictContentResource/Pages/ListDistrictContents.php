<?php

declare(strict_types=1);

namespace App\Filament\Resources\DistrictContentResource\Pages;

use App\Filament\Resources\DistrictContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDistrictContents extends ListRecords
{
    protected static string $resource = DistrictContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
