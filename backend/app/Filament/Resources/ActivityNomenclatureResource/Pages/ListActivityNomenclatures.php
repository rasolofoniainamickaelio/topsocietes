<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityNomenclatureResource\Pages;

use App\Filament\Resources\ActivityNomenclatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityNomenclatures extends ListRecords
{
    protected static string $resource = ActivityNomenclatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
