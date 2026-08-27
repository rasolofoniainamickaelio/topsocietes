<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Taxonomy\Models\ActivityNomenclature;
use App\Enums\PermissionName;
use App\Filament\Resources\ActivityNomenclatureResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityNomenclatureResource extends Resource
{
    protected static ?string $model = ActivityNomenclature::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Taxonomie';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::TaxonomyManage->value) ?? false;
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
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('country_id')->relationship('country', 'name')->searchable(),
            TextInput::make('version')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('version'),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityNomenclatures::route('/'),
            'create' => Pages\CreateActivityNomenclature::route('/create'),
            'edit' => Pages\EditActivityNomenclature::route('/{record}/edit'),
        ];
    }
}
