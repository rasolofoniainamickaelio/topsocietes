<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportMappingResource\Pages;

use App\Filament\Resources\ImportMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImportMapping extends EditRecord
{
    protected static string $resource = ImportMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
