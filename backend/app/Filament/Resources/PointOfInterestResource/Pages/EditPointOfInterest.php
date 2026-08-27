<?php

declare(strict_types=1);

namespace App\Filament\Resources\PointOfInterestResource\Pages;

use App\Filament\Resources\PointOfInterestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPointOfInterest extends EditRecord
{
    protected static string $resource = PointOfInterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
