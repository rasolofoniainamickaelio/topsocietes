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
use Filament\Tables\Actions\Action;
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
            ->actions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CompanyClaim $record): bool => in_array($record->status, [
                        CompanyClaimStatus::Pending,
                        CompanyClaimStatus::Verifying,
                    ], true))
                    ->authorize('review')
                    ->requiresConfirmation()
                    ->action(fn (CompanyClaim $record) => $record->update([
                        'status' => CompanyClaimStatus::Approved,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ])),
                Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (CompanyClaim $record): bool => in_array($record->status, [
                        CompanyClaimStatus::Pending,
                        CompanyClaimStatus::Verifying,
                    ], true))
                    ->authorize('review')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notes')->label('Motif du refus')->required(),
                    ])
                    ->action(fn (CompanyClaim $record, array $data) => $record->update([
                        'status' => CompanyClaimStatus::Rejected,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                        'notes' => $data['notes'],
                    ])),
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
