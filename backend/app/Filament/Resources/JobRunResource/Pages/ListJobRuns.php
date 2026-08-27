<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobRunResource\Pages;

use App\Filament\Resources\JobRunResource;
use Filament\Resources\Pages\ListRecords;

class ListJobRuns extends ListRecords
{
    protected static string $resource = JobRunResource::class;
}
