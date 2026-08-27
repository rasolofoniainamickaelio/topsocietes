<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyClaimResource\Pages;

use App\Filament\Resources\CompanyClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyClaim extends EditRecord
{
    protected static string $resource = CompanyClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
