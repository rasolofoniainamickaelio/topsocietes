<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContentSectionResource\Pages;

use App\Filament\Resources\ContentSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentSections extends ListRecords
{
    protected static string $resource = ContentSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
