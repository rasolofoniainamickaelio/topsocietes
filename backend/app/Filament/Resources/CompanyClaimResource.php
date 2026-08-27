<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Company\Enums\ClaimVerificationMethod;
use App\Domain\Company\Enums\CompanyClaimStatus;
use App\Domain\Company\Models\CompanyClaim;
use App\Filament\Resources\CompanyClaimResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyClaimResource extends Resource
{
    protected static ?string $model = CompanyClaim::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Entreprises';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('company_id')->relationship('company', 'legal_name')->searchable()->required(),
            Select::make('user_id')->relationship('user', 'name')->searchable()->required(),
            Select::make('status')->options(EnumOptions::for(CompanyClaimStatus::class))->required(),
            Select::make('verification_method')->options(EnumOptions::for(ClaimVerificationMethod::class))->required(),
            TextInput::make('evidence_path')->disabled()->dehydrated(false),
            Select::make('reviewed_by')->relationship('reviewer', 'name')->searchable(),
            DateTimePicker::make('reviewed_at'),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.legal_name')->label('Entreprise')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Demandeur')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('verification_method')->badge()->toggleable(),
                TextColumn::make('reviewer.name')->label('Revu par')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(CompanyClaimStatus::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyClaims::route('/'),
            'create' => Pages\CreateCompanyClaim::route('/create'),
            'edit' => Pages\EditCompanyClaim::route('/{record}/edit'),
        ];
    }
}
