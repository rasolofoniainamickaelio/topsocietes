<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ai\Actions\RetryAiGenerationJobAction;
use App\Domain\Ai\Enums\AiProvider;
use App\Domain\Ai\Enums\GenerationStatus;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Enums\PermissionName;
use App\Filament\Resources\AiGenerationJobResource\Pages;
use App\Filament\Resources\AiGenerationJobResource\RelationManagers\LogsRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Jamais lu ni déclenché par une page publique (CLAUDE.md §6.1) — ce
 * back-office ne fait qu'observer l'historique du pipeline batch.
 */
class AiGenerationJobResource extends Resource
{
    protected static ?string $model = AiGenerationJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'IA';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::AiPipelineManage->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('target_type')->label('Cible'),
            TextEntry::make('target_id')->label('ID cible'),
            TextEntry::make('section'),
            TextEntry::make('status')->badge(),
            TextEntry::make('provider')->badge(),
            TextEntry::make('model'),
            TextEntry::make('attempts'),
            TextEntry::make('input_tokens')->label('Tokens (entrée)'),
            TextEntry::make('output_tokens')->label('Tokens (sortie)'),
            TextEntry::make('cost_cents')->label('Coût (¢)'),
            TextEntry::make('error_message')->label('Erreur')->columnSpanFull(),
            TextEntry::make('started_at')->dateTime(),
            TextEntry::make('finished_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('target_type')->label('Cible'),
                TextColumn::make('section')->label('Section'),
                TextColumn::make('status')->badge(),
                TextColumn::make('provider')->badge()->toggleable(),
                TextColumn::make('attempts')->sortable(),
                TextColumn::make('cost_cents')->label('Coût (¢)')->numeric()->sortable(),
                TextColumn::make('finished_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(GenerationStatus::class)),
                SelectFilter::make('provider')->options(EnumOptions::for(AiProvider::class)),
            ])
            ->actions([
                Action::make('retry')
                    ->label('Relancer')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (AiGenerationJob $record): bool => $record->status === GenerationStatus::Failed
                        && (auth()->user()?->can(PermissionName::AiPipelineManage->value) ?? false))
                    ->requiresConfirmation()
                    ->action(function (AiGenerationJob $record, RetryAiGenerationJobAction $action): void {
                        try {
                            $action->execute($record);
                            Notification::make()->title('Génération remise en file')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Relance impossible')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiGenerationJobs::route('/'),
            'create' => Pages\LaunchAiGeneration::route('/create'),
            'view' => Pages\ViewAiGenerationJob::route('/{record}'),
        ];
    }
}
