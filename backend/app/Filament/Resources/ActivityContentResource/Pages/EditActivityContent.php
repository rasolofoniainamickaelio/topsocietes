<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityContentResource\Pages;

use App\Filament\Resources\ActivityContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActivityContent extends EditRecord
{
    protected static string $resource = ActivityContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
