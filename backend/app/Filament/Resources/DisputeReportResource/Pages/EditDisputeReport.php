<?php

declare(strict_types=1);

namespace App\Filament\Resources\DisputeReportResource\Pages;

use App\Filament\Resources\DisputeReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisputeReport extends EditRecord
{
    protected static string $resource = DisputeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
