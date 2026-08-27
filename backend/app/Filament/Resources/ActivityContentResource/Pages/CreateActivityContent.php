<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityContentResource\Pages;

use App\Filament\Resources\ActivityContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityContent extends CreateRecord
{
    protected static string $resource = ActivityContentResource::class;
}
