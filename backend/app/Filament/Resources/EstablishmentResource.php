<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Establishment;
use App\Enums\PermissionName;
use App\Filament\Resources\EstablishmentResource\Pages;
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

class EstablishmentResource extends Resource
{
    protected static ?string $model = Establishment::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Entreprises';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::CompaniesManage->value) ?? false;
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
            Select::make('company_id')->relationship('company', 'legal_name')->searchable()->required(),
            Select::make('country_id')->relationship('country', 'name')->searchable()->required(),
            TextInput::make('national_id')->required()->maxLength(255),
            Toggle::make('is_headquarters'),
            TextInput::make('street_number')->maxLength(255),
            TextInput::make('street_name')->maxLength(255),
            TextInput::make('address_line2')->maxLength(255),
            TextInput::make('postal_code')->maxLength(255),
            Select::make('city_id')->relationship('city', 'name')->searchable(),
            Select::make('district_id')->relationship('district', 'name')->searchable(),
            Select::make('activity_id')->relationship('activity', 'public_label')->searchable(),
            Select::make('geocoding_status')->options(EnumOptions::for(GeocodingStatus::class)),
            Select::make('status')->options(EnumOptions::for(CompanyStatus::class))->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.legal_name')->label('Entreprise')->searchable()->sortable(),
                TextColumn::make('national_id')->label('Identifiant')->searchable(),
                IconColumn::make('is_headquarters')->boolean()->label('Siège'),
                TextColumn::make('street_name')->label('Rue')->toggleable(),
                TextColumn::make('city.name')->label('Ville')->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(CompanyStatus::class)),
                SelectFilter::make('city')->relationship('city', 'name')->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstablishments::route('/'),
            'create' => Pages\CreateEstablishment::route('/create'),
            'edit' => Pages\EditEstablishment::route('/{record}/edit'),
        ];
    }
}
