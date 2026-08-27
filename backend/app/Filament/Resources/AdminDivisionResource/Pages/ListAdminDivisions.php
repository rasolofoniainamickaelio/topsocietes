<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminDivisionResource\Pages;

use App\Filament\Resources\AdminDivisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdminDivisions extends ListRecords
{
    protected static string $resource = AdminDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
