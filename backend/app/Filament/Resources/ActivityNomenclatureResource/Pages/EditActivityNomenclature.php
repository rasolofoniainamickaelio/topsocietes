<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityNomenclatureResource\Pages;

use App\Filament\Resources\ActivityNomenclatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActivityNomenclature extends EditRecord
{
    protected static string $resource = ActivityNomenclatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
