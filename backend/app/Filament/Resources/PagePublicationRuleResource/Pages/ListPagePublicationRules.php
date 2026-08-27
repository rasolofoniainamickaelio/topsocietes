<?php

declare(strict_types=1);

namespace App\Filament\Resources\PagePublicationRuleResource\Pages;

use App\Filament\Resources\PagePublicationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPagePublicationRules extends ListRecords
{
    protected static string $resource = PagePublicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
