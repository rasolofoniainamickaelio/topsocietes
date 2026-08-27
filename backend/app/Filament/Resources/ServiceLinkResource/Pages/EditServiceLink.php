<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceLinkResource\Pages;

use App\Filament\Resources\ServiceLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceLink extends EditRecord
{
    protected static string $resource = ServiceLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
