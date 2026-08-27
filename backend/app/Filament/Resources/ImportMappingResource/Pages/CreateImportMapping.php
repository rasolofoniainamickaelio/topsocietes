<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportMappingResource\Pages;

use App\Filament\Resources\ImportMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportMapping extends CreateRecord
{
    protected static string $resource = ImportMappingResource::class;
}
