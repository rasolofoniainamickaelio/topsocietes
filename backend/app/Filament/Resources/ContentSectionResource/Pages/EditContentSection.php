<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContentSectionResource\Pages;

use App\Filament\Resources\ContentSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentSection extends EditRecord
{
    protected static string $resource = ContentSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
