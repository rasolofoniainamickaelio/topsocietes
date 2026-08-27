<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Cache pré-calculé (Phase 4, ComputeCompanyNearbyPoisAction) — jamais
 * édité à la main, uniquement consulté.
 */
class NearbyPoisRelationManager extends RelationManager
{
    protected static string $relationship = 'nearbyPois';

    protected static ?string $title = 'POI à proximité';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('poi.name')->label('Point d\'intérêt'),
                TextColumn::make('poi.category')->badge(),
                TextColumn::make('distance_m')->label('Distance (m)')->numeric()->sortable(),
                TextColumn::make('rank')->sortable(),
                TextColumn::make('generated_at')->dateTime(),
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
