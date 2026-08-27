<?php

declare(strict_types=1);

namespace App\Filament\Resources\PointOfInterestResource\Pages;

use App\Filament\Resources\PointOfInterestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPointsOfInterest extends ListRecords
{
    protected static string $resource = PointOfInterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
