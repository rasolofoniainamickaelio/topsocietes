<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceLinkResource\Pages;

use App\Filament\Resources\ServiceLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceLinks extends ListRecords
{
    protected static string $resource = ServiceLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
