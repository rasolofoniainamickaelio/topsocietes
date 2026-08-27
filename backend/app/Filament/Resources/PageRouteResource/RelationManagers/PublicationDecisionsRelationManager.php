<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageRouteResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique du scoring de publication (docs/DATABASE.md §4-I) — généré
 * par le job d'évaluation, jamais édité à la main.
 */
class PublicationDecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'publicationDecisions';

    protected static ?string $title = 'Décisions de publication';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('score')->numeric()->sortable(),
                TextColumn::make('decision')->badge(),
                TextColumn::make('evaluated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('evaluated_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
