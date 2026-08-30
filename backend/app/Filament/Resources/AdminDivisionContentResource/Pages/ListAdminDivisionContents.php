<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminDivisionContentResource\Pages;

use App\Filament\Resources\AdminDivisionContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminDivisionContents extends ListRecords
{
    protected static string $resource = AdminDivisionContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
