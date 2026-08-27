<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Content\Enums\SourceProvider;
use App\Domain\Content\Models\Source;
use App\Enums\PermissionName;
use App\Filament\Resources\SourceResource\Pages;
use App\Filament\Resources\SourceResource\RelationManagers\FactsRelationManager;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Contenus';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::SourcesManage->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('provider')->options(EnumOptions::for(SourceProvider::class))->required(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('base_url')->url()->maxLength(255),
            TextInput::make('license')->maxLength(255),
            Select::make('country_id')->relationship('country', 'name')->searchable(),
            TextInput::make('reliability_score')->numeric()->minValue(0)->maxValue(100),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('provider')->badge(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('reliability_score')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('provider')->options(EnumOptions::for(SourceProvider::class)),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            FactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
        ];
    }
}
