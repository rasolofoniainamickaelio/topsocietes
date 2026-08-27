<?php

declare(strict_types=1);

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSourceDocuments extends ListRecords
{
    protected static string $resource = SourceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
