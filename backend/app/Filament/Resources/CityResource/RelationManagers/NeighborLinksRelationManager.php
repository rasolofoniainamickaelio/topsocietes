<?php

declare(strict_types=1);

namespace App\Filament\Resources\CityResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Cache pré-calculé (Phase 4, ComputeCityNeighborsAction) — jamais édité à
 * la main, uniquement consulté.
 */
class NeighborLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'neighborLinks';

    protected static ?string $title = 'Communes voisines';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('neighborCity.name')->label('Ville voisine'),
                TextColumn::make('distance_m')->label('Distance (m)')->numeric()->sortable(),
                TextColumn::make('rank')->sortable(),
            ])
            ->defaultSort('rank')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
