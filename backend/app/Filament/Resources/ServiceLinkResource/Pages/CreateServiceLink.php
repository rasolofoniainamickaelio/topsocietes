<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceLinkResource\Pages;

use App\Filament\Resources\ServiceLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceLink extends CreateRecord
{
    protected static string $resource = ServiceLinkResource::class;
}
