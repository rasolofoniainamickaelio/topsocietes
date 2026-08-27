<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Geo\Models\District;
use App\Enums\PermissionName;
use App\Filament\Resources\DistrictResource\Pages;
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

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

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
            Select::make('city_id')->relationship('city', 'name')->searchable()->required(),
            TextInput::make('code')->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255),
            TextInput::make('population')->numeric(),
            TextInput::make('area_km2')->numeric(),
            Toggle::make('has_local_content'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('city.name')->label('Ville')->sortable(),
                TextColumn::make('population')->numeric()->toggleable(),
                TextColumn::make('companies_count')->label('Entreprises')->numeric()->sortable(),
                IconColumn::make('has_local_content')->boolean()->label('Contenu local'),
            ])
            ->filters([
                SelectFilter::make('city')->relationship('city', 'name')->searchable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
