<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Import\Enums\ImportStatus;
use App\Domain\Import\Models\ImportBatch;
use App\Enums\PermissionName;
use App\Filament\Resources\ImportBatchResource\Pages;
use App\Filament\Resources\ImportBatchResource\RelationManagers\ErrorsRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Import';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::ImportsManage->value) ?? false;
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

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('filename'),
            TextEntry::make('format')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('triggeredBy.name')->label('Lancé par')->placeholder('CLI / système'),
            TextEntry::make('total_rows'),
            TextEntry::make('processed_rows'),
            TextEntry::make('created_count'),
            TextEntry::make('updated_count'),
            TextEntry::make('skipped_count'),
            TextEntry::make('error_count'),
            TextEntry::make('started_at')->dateTime(),
            TextEntry::make('finished_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->searchable()->sortable(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('processed_rows')->label('Traitées')->numeric(),
                TextColumn::make('error_count')->label('Erreurs')->numeric()->sortable(),
                TextColumn::make('started_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(ImportStatus::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'view' => Pages\ViewImportBatch::route('/{record}'),
        ];
    }
}
