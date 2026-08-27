<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique immuable du cycle masqué/démasqué (docs/DATABASE.md §4-J) —
 * jamais édité à la main, uniquement consulté.
 */
class ContactVisibilityEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'contactVisibilityEvents';

    protected static ?string $title = 'Historique de visibilité des contacts';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('contact.value')->label('Contact'),
                TextColumn::make('action')->badge(),
                TextColumn::make('triggered_by')->label('Déclenché par')->badge(),
                TextColumn::make('user.name')->label('Utilisateur'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
