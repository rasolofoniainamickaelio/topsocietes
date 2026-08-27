<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityMappingResource\Pages;

use App\Filament\Resources\ActivityMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityMappings extends ListRecords
{
    protected static string $resource = ActivityMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
