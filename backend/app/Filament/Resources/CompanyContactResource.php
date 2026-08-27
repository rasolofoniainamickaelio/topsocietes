<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Company\Enums\ContactSource;
use App\Domain\Company\Enums\ContactType;
use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\CompanyContact;
use App\Filament\Resources\CompanyContactResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Le masquage CLAUDE.md §6.3 concerne l'API publique — ici, le staff avec
 * `contacts.manage` voit les vraies coordonnées, c'est le but de l'écran.
 */
class CompanyContactResource extends Resource
{
    protected static ?string $model = CompanyContact::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationGroup = 'Entreprises';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('company_id')->relationship('company', 'legal_name')->searchable()->required(),
            Select::make('type')->options(EnumOptions::for(ContactType::class))->required(),
            TextInput::make('value')->required()->maxLength(255),
            Toggle::make('is_monetized'),
            Select::make('visibility')->options(EnumOptions::for(ContactVisibility::class))->required(),
            Select::make('source')->options(EnumOptions::for(ContactSource::class))->required(),
            DateTimePicker::make('verified_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.legal_name')->label('Entreprise')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('value'),
                TextColumn::make('visibility')->badge(),
                IconColumn::make('is_monetized')->boolean(),
                TextColumn::make('verified_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(EnumOptions::for(ContactType::class)),
                SelectFilter::make('visibility')->options(EnumOptions::for(ContactVisibility::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyContacts::route('/'),
            'create' => Pages\CreateCompanyContact::route('/create'),
            'edit' => Pages\EditCompanyContact::route('/{record}/edit'),
        ];
    }
}
