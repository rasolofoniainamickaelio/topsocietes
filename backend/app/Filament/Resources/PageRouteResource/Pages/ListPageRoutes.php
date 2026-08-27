<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageRouteResource\Pages;

use App\Filament\Resources\PageRouteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageRoutes extends ListRecords
{
    protected static string $resource = PageRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
