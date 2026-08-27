<?php

declare(strict_types=1);

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSourceDocument extends CreateRecord
{
    protected static string $resource = SourceDocumentResource::class;
}
