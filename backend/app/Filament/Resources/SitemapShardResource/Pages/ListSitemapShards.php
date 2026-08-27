<?php

declare(strict_types=1);

namespace App\Filament\Resources\SitemapShardResource\Pages;

use App\Filament\Resources\SitemapShardResource;
use Filament\Resources\Pages\ListRecords;

class ListSitemapShards extends ListRecords
{
    protected static string $resource = SitemapShardResource::class;
}
