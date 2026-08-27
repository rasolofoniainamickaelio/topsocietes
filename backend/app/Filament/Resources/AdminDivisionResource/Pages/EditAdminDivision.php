<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminDivisionResource\Pages;

use App\Filament\Resources\AdminDivisionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminDivision extends EditRecord
{
    protected static string $resource = AdminDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
