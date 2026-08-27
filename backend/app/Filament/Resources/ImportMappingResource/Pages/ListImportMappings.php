<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportMappingResource\Pages;

use App\Filament\Resources\ImportMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportMappings extends ListRecords
{
    protected static string $resource = ImportMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
