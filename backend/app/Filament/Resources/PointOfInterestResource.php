<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Geo\Enums\PoiCategory;
use App\Domain\Geo\Enums\PoiProvider;
use App\Domain\Geo\Models\PointOfInterest;
use App\Enums\PermissionName;
use App\Filament\Resources\PointOfInterestResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PointOfInterestResource extends Resource
{
    protected static ?string $model = PointOfInterest::class;

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
            Select::make('city_id')->relationship('city', 'name')->searchable(),
            Select::make('district_id')->relationship('district', 'name')->searchable(),
            TextInput::make('external_ref')->maxLength(255),
            Select::make('provider')->options(EnumOptions::for(PoiProvider::class))->required(),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('category')->options(EnumOptions::for(PoiCategory::class))->required(),
            TextInput::make('subcategory')->maxLength(255),
            Textarea::make('description')->columnSpanFull(),
            KeyValue::make('attributes')->columnSpanFull(),
            TextInput::make('editorial_score')->numeric(),
            Toggle::make('is_publishable'),
            DateTimePicker::make('last_synced_at')->disabled()->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('city.name')->label('Ville')->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('provider')->badge()->toggleable(),
                TextColumn::make('editorial_score')->numeric()->sortable(),
                IconColumn::make('is_publishable')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->options(EnumOptions::for(PoiCategory::class)),
                SelectFilter::make('city')->relationship('city', 'name')->searchable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointsOfInterest::route('/'),
            'create' => Pages\CreatePointOfInterest::route('/create'),
            'edit' => Pages\EditPointOfInterest::route('/{record}/edit'),
        ];
    }
}
