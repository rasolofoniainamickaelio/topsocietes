<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Geo\Models\City;
use App\Enums\PermissionName;
use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers\NeighborLinksRelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Géographie';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::GeoManage->value) ?? false;
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
            Select::make('admin_division_id')->relationship('adminDivision', 'name')->searchable(),
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255),
            TagsInput::make('postal_codes'),
            TextInput::make('population')->numeric(),
            TextInput::make('area_km2')->numeric(),
            TextInput::make('altitude')->numeric(),
            Toggle::make('has_local_content'),
            TextInput::make('wikidata_id')->label('Identifiant Wikidata')->maxLength(255)->helperText('Ex. Q456 — laisser vide pour ne pas collecter depuis Wikidata'),
            TextInput::make('wikipedia_title')->label('Titre Wikipedia')->maxLength(255)->helperText('Titre exact de la page — laisser vide pour ne pas collecter depuis Wikipedia'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country.name')->label('Pays')->sortable(),
                TextColumn::make('population')->numeric()->sortable(),
                TextColumn::make('companies_count')->label('Entreprises')->numeric()->sortable(),
                IconColumn::make('has_local_content')->boolean()->label('Contenu local'),
            ])
            ->filters([
                SelectFilter::make('country')->relationship('country', 'name')->searchable(),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            NeighborLinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
