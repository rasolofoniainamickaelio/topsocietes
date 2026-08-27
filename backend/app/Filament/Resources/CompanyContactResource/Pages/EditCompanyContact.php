<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyContactResource\Pages;

use App\Filament\Resources\CompanyContactResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyContact extends EditRecord
{
    protected static string $resource = CompanyContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
