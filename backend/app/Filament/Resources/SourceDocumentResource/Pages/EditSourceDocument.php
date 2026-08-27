<?php

declare(strict_types=1);

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSourceDocument extends EditRecord
{
    protected static string $resource = SourceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
