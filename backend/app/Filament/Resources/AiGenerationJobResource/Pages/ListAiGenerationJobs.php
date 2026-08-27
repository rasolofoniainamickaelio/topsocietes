<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiGenerationJobResource\Pages;

use App\Filament\Resources\AiGenerationJobResource;
use Filament\Resources\Pages\ListRecords;

class ListAiGenerationJobs extends ListRecords
{
    protected static string $resource = AiGenerationJobResource::class;
}
