<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyClaimResource\Pages;

use App\Filament\Resources\CompanyClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyClaims extends ListRecords
{
    protected static string $resource = CompanyClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
