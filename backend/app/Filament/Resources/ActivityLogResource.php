<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RoleName;
use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Journal d'audit (Phase 21) : lecture seule sur `activity_log`, alimenté
 * par `LogsActivity` sur les modèles sensibles (Company, Subscription,
 * Payment, User, DisputeReport, Redirect) — jamais éditable ni supprimable
 * depuis le back-office, un journal d'audit qui peut être modifié n'en est
 * plus un. Réservé à super_admin/admin, même patron que `JobRunResource`
 * (pas de permission dédiée au catalogue ADR 0002 pour ce concept
 * transversal).
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Utilisateurs';

    protected static ?string $navigationLabel = "Journal d'activité";

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

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('subject_type')->label('Type'),
            TextEntry::make('subject_id')->label('ID'),
            TextEntry::make('causer.name')->label('Auteur')->placeholder('Système'),
            TextEntry::make('event'),
            TextEntry::make('description'),
            TextEntry::make('created_at')->dateTime(),
            KeyValueEntry::make('properties.attributes')->label('Nouvelles valeurs')->columnSpanFull(),
            KeyValueEntry::make('properties.old')->label('Anciennes valeurs')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject_type')->label('Type')->formatStateUsing(fn (?string $state): string => $state !== null ? class_basename($state) : '—')->sortable(),
                TextColumn::make('subject_id')->label('ID'),
                TextColumn::make('causer.name')->label('Auteur')->placeholder('Système'),
                TextColumn::make('event')->badge(),
                TextColumn::make('description')->limit(60),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')->options([
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
