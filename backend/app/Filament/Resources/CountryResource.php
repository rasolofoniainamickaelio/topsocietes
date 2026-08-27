<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Geo\Models\Country;
use App\Enums\PermissionName;
use App\Filament\Resources\CountryResource\Pages;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

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
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('iso_alpha2')->required()->maxLength(2),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('subdomain')->required()->maxLength(255),
            TextInput::make('default_locale')->required()->maxLength(10),
            TextInput::make('currency')->required()->maxLength(3),
            TextInput::make('timezone')->required()->maxLength(255),
            TextInput::make('activity_nomenclature_code')->maxLength(255),
            Toggle::make('is_active')->default(true),
            KeyValue::make('admin_level_labels')->columnSpanFull(),
            Textarea::make('identifier_config')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Textarea::make('url_patterns')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Textarea::make('source_config')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Textarea::make('settings')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('subdomain')->searchable(),
                TextColumn::make('currency'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
