<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RoleName;
use App\Filament\Resources\FailedJobResource\Pages;
use App\Models\FailedJob;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Visualiseur d'erreurs applicatives (Phase 21) : `failed_jobs` est
 * alimentée automatiquement par le worker de queue pour N'IMPORTE lequel des
 * jobs du système (import, géoloc, IA, sitemaps...) sans qu'aucun d'eux
 * n'ait à s'instrumenter individuellement — contrairement à `ImportBatch`/
 * `AiGenerationJob`, qui restent les sources de vérité pour LEUR domaine.
 * Suppression autorisée (équivalent `queue:forget`) : ce n'est pas un
 * journal d'audit, juste une file d'incidents à purger une fois traités.
 */
class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Utilisateurs';

    protected static ?string $navigationLabel = 'Erreurs';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('job_name')->label('Job')->state(fn (FailedJob $record) => self::jobName($record)),
            TextEntry::make('connection'),
            TextEntry::make('queue'),
            TextEntry::make('failed_at')->dateTime(),
            TextEntry::make('exception')->columnSpanFull()->extraAttributes(['class' => 'font-mono text-xs whitespace-pre-wrap']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('job_name')->label('Job')->state(fn (FailedJob $record) => self::jobName($record))->searchable(query: fn ($query, string $search) => $query->where('payload', 'like', "%{$search}%")),
                TextColumn::make('queue'),
                TextColumn::make('exception')->limit(80)->label('Erreur'),
                TextColumn::make('failed_at')->dateTime()->sortable(),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->defaultSort('failed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFailedJobs::route('/'),
            'view' => Pages\ViewFailedJob::route('/{record}'),
        ];
    }

    private static function jobName(FailedJob $record): string
    {
        $payload = json_decode((string) $record->payload, true);

        $className = $payload['displayName'] ?? $payload['data']['commandName'] ?? null;

        return is_string($className) ? class_basename($className) : 'Job inconnu';
    }
}
