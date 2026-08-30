<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminDivisionContentResource\Pages;

use App\Filament\Resources\AdminDivisionContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminDivisionContent extends EditRecord
{
    protected static string $resource = AdminDivisionContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
