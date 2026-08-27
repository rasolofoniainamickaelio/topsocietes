<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\SitemapShard;
use App\Enums\PermissionName;
use App\Filament\Resources\SitemapShardResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SitemapShardResource extends Resource
{
    protected static ?string $model = SitemapShard::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'SEO';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::SeoManage->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('index'),
                TextColumn::make('url_count')->label('URLs')->numeric()->sortable(),
                IconColumn::make('is_stale')->boolean()->label('Périmé'),
                TextColumn::make('generated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(EnumOptions::for(PageType::class)),
            ])
            ->defaultSort('generated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSitemapShards::route('/'),
        ];
    }
}
