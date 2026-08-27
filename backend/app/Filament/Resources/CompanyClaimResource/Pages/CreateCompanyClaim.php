<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyClaimResource\Pages;

use App\Filament\Resources\CompanyClaimResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyClaim extends CreateRecord
{
    protected static string $resource = CompanyClaimResource::class;
}
