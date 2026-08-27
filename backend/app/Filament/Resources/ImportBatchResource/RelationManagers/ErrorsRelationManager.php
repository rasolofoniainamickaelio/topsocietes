<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportBatchResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Lignes en erreur (docs/DATABASE.md §4-H) — `is_resolved` est mis à jour
 * par la commande de reprise, jamais à la main ici.
 */
class ErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    protected static ?string $title = 'Erreurs';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('row_number')
            ->columns([
                TextColumn::make('row_number')->sortable(),
                TextColumn::make('error_code'),
                TextColumn::make('error_message')->limit(60),
                IconColumn::make('is_resolved')->boolean(),
            ])
            ->defaultSort('row_number')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
