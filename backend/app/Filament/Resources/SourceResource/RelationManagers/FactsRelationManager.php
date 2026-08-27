<?php

declare(strict_types=1);

namespace App\Filament\Resources\SourceResource\RelationManagers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Faits sourcés (docs/DATABASE.md §4-F) : `is_usable` est une colonne
 * générée (confidence_score >= 60 ET corroboration_count >= 1), jamais
 * éditable directement.
 */
class FactsRelationManager extends RelationManager
{
    protected static string $relationship = 'facts';

    protected static ?string $title = 'Faits';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('key')->required()->maxLength(255),
            TextInput::make('value')->required()->maxLength(1000),
            TextInput::make('source_url')->url()->maxLength(255),
            TextInput::make('confidence_score')->numeric()->minValue(0)->maxValue(100)->required(),
            TextInput::make('corroboration_count')->numeric()->minValue(0)->required()->default(1),
            DateTimePicker::make('last_verified_at'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('key')
            ->columns([
                TextColumn::make('key'),
                TextColumn::make('value')->limit(50),
                TextColumn::make('confidence_score')->numeric()->sortable(),
                TextColumn::make('corroboration_count')->numeric(),
                IconColumn::make('is_usable')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
