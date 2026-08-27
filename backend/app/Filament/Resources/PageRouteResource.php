<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Seo\Enums\NoindexReason;
use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Enums\SitemapChangeFrequency;
use App\Domain\Seo\Models\PageRoute;
use App\Enums\PermissionName;
use App\Filament\Resources\PageRouteResource\Pages;
use App\Filament\Resources\PageRouteResource\RelationManagers\PublicationDecisionsRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PageRouteResource extends Resource
{
    protected static ?string $model = PageRoute::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'SEO';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::SeoManage->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('country_id')->relationship('country', 'name')->searchable()->required(),
            TextInput::make('path')->required()->maxLength(255),
            TextInput::make('entity_type')->maxLength(255),
            TextInput::make('entity_id')->numeric(),
            Select::make('page_type')->options(EnumOptions::for(PageType::class))->required(),
            Select::make('canonical_route_id')->relationship('canonicalRoute', 'path')->searchable(),
            Toggle::make('is_indexable')->default(true),
            Select::make('noindex_reason')->options(EnumOptions::for(NoindexReason::class)),
            TextInput::make('priority')->numeric()->minValue(0)->maxValue(1)->step(0.1),
            Select::make('changefreq')->options(EnumOptions::for(SitemapChangeFrequency::class)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('path')->searchable()->sortable(),
                TextColumn::make('page_type')->badge(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                IconColumn::make('is_indexable')->boolean(),
                TextColumn::make('priority')->numeric(),
            ])
            ->filters([
                SelectFilter::make('page_type')->options(EnumOptions::for(PageType::class)),
            ])
            ->defaultSort('path');
    }

    public static function getRelations(): array
    {
        return [
            PublicationDecisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageRoutes::route('/'),
            'create' => Pages\CreatePageRoute::route('/create'),
            'edit' => Pages\EditPageRoute::route('/{record}/edit'),
        ];
    }
}
