<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Company\Enums\CompanyContentStatus;
use App\Domain\Company\Enums\CompanyStatus;
use App\Domain\Company\Enums\GeocodingStatus;
use App\Domain\Company\Models\Company;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers\ContactVisibilityEventsRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\NearbyPoisRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DatePicker;
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

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Entreprises';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('country_id')->relationship('country', 'name')->searchable()->required(),
            TextInput::make('national_id')->required()->maxLength(255),
            TextInput::make('legal_name')->required()->maxLength(255),
            TextInput::make('trade_name')->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255),
            TextInput::make('legal_form_code')->maxLength(255),
            TextInput::make('legal_form_label')->maxLength(255),
            Select::make('status')->options(EnumOptions::for(CompanyStatus::class))->required(),
            DatePicker::make('created_date'),
            DatePicker::make('ceased_date'),
            Select::make('activity_id')->relationship('activity', 'public_label')->searchable(),
            TextInput::make('headcount_range')->maxLength(255),
            Select::make('city_id')->relationship('city', 'name')->searchable(),
            Select::make('district_id')->relationship('district', 'name')->searchable(),
            Select::make('admin_division_id')->relationship('adminDivision', 'name')->searchable(),
            Select::make('geocoding_status')->options(EnumOptions::for(GeocodingStatus::class)),
            Select::make('content_status')->options(EnumOptions::for(CompanyContentStatus::class)),
            Toggle::make('is_indexable'),
            Textarea::make('about_text')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legal_name')->searchable()->sortable(),
                TextColumn::make('trade_name')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('city.name')->label('Ville')->sortable(),
                TextColumn::make('activity.public_label')->label('Activité')->toggleable(),
                TextColumn::make('content_status')->badge()->label('Contenu'),
                IconColumn::make('is_indexable')->boolean()->label('Indexable'),
                TextColumn::make('created_date')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(CompanyStatus::class)),
                SelectFilter::make('content_status')->options(EnumOptions::for(CompanyContentStatus::class)),
                SelectFilter::make('city')->relationship('city', 'name')->searchable(),
            ])
            ->defaultSort('legal_name');
    }

    public static function getRelations(): array
    {
        return [
            NearbyPoisRelationManager::class,
            ContactVisibilityEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
