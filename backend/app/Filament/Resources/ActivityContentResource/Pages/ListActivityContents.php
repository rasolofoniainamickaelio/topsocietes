<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityContentResource\Pages;

use App\Filament\Resources\ActivityContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityContents extends ListRecords
{
    protected static string $resource = ActivityContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
