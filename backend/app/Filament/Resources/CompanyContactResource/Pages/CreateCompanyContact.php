<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyContactResource\Pages;

use App\Filament\Resources\CompanyContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyContact extends CreateRecord
{
    protected static string $resource = CompanyContactResource::class;
}
