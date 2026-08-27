<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Import\Models\ImportMapping;
use App\Enums\PermissionName;
use App\Filament\Resources\ImportMappingResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ImportMappingResource extends Resource
{
    protected static ?string $model = ImportMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Import';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::ImportsManage->value) ?? false;
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
            Select::make('country_id')->relationship('country', 'name')->searchable()->required(),
            TextInput::make('name')->required()->maxLength(255),
            Textarea::make('column_map')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Textarea::make('transformers')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Toggle::make('is_default'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country.name')->label('Pays')->sortable(),
                IconColumn::make('is_default')->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportMappings::route('/'),
            'create' => Pages\CreateImportMapping::route('/create'),
            'edit' => Pages\EditImportMapping::route('/{record}/edit'),
        ];
    }
}
