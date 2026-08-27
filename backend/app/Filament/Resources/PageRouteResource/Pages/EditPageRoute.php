<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageRouteResource\Pages;

use App\Filament\Resources\PageRouteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPageRoute extends EditRecord
{
    protected static string $resource = PageRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
