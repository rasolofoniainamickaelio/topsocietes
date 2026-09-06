<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Moderation\Actions\ApplyDisputeCorrectionAction;
use App\Domain\Moderation\Enums\DisputeStatus;
use App\Domain\Moderation\Models\DisputeReport;
use App\Filament\Resources\DisputeReportResource\Pages;
use App\Filament\Resources\DisputeReportResource\RelationManagers\EventsRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class DisputeReportResource extends Resource
{
    protected static ?string $model = DisputeReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Modération';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('company_id')->relationship('company', 'legal_name')->searchable()->required(),
            TextInput::make('field')->required()->maxLength(100),
            TextInput::make('current_value')->maxLength(1000),
            TextInput::make('proposed_value')->required()->maxLength(1000),
            Textarea::make('reason')->required()->columnSpanFull(),
            TextInput::make('reporter_name')->required()->maxLength(255),
            TextInput::make('reporter_email')->email()->required()->maxLength(255),
            TextInput::make('reporter_phone')->tel()->maxLength(50),
            TextInput::make('evidence_path')->disabled()->dehydrated(false),
            Select::make('status')->options(EnumOptions::for(DisputeStatus::class))->required(),
            Select::make('assigned_to')->relationship('assignee', 'name')->searchable(),
            Textarea::make('internal_note')->columnSpanFull(),
            DateTimePicker::make('resolved_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.legal_name')->label('Entreprise')->searchable()->sortable(),
                TextColumn::make('field')->label('Champ'),
                TextColumn::make('status')->badge(),
                TextColumn::make('assignee.name')->label('Assigné à')->toggleable(),
                TextColumn::make('reporter_name')->label('Signalé par')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(DisputeStatus::class)),
            ])
            ->actions([
                Action::make('apply')
                    ->label('Appliquer la correction')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (DisputeReport $record): bool => $record->status === DisputeStatus::Accepted
                        && (auth()->user()?->can('review', $record) ?? false))
                    ->requiresConfirmation()
                    ->modalDescription(fn (DisputeReport $record): string => "Écrit « {$record->proposed_value} » dans le champ « {$record->field} » de la fiche entreprise, puis marque la contestation comme appliquée.")
                    ->action(function (DisputeReport $record, ApplyDisputeCorrectionAction $action): void {
                        try {
                            $action->execute($record);
                            Notification::make()->title('Correction appliquée')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Application impossible')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputeReports::route('/'),
            'create' => Pages\CreateDisputeReport::route('/create'),
            'edit' => Pages\EditDisputeReport::route('/{record}/edit'),
        ];
    }
}
