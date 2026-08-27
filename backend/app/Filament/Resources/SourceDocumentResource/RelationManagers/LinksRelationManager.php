<?php

declare(strict_types=1);

namespace App\Filament\Resources\SourceDocumentResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Trace quel contenu publié s'appuie sur ce document (docs/DATABASE.md
 * §4-F) — généré au moment de la publication, jamais édité à la main.
 */
class LinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    protected static ?string $title = 'Contenus liés';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('content_type')->label('Type de contenu'),
                TextColumn::make('content_id')->label('ID contenu'),
                TextColumn::make('fact.key')->label('Fait lié'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
