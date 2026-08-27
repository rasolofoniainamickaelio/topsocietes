<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\JobRunStatus;
use App\Enums\RoleName;
use App\Filament\Resources\JobRunResource\Pages;
use App\Filament\Support\EnumOptions;
use App\Models\JobRun;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * `JobRun` reste hors `Domain/` (suivi opérationnel transversal, pas un
 * concept métier d'un seul domaine — Phase 3) : aucune permission dédiée
 * n'existe pour lui dans le catalogue ADR 0002, d'où une autorisation par
 * rôle direct plutôt que par `PermissionName`.
 */
class JobRunResource extends Resource
{
    protected static ?string $model = JobRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'Utilisateurs';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('queue'),
                TextColumn::make('status')->badge(),
                TextColumn::make('attempts')->sortable(),
                TextColumn::make('progress_current')->label('Progression'),
                TextColumn::make('duration_ms')->label('Durée (ms)')->numeric()->sortable(),
                TextColumn::make('finished_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(JobRunStatus::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobRuns::route('/'),
        ];
    }
}
