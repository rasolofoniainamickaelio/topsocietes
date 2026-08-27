<?php

declare(strict_types=1);

namespace App\Filament\Resources\PagePublicationRuleResource\Pages;

use App\Filament\Resources\PagePublicationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPagePublicationRule extends EditRecord
{
    protected static string $resource = PagePublicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
