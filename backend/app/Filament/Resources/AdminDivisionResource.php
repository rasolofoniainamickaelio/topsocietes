<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Geo\Models\AdminDivision;
use App\Enums\PermissionName;
use App\Filament\Resources\AdminDivisionResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdminDivisionResource extends Resource
{
    protected static ?string $model = AdminDivision::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Géographie';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::GeoManage->value) ?? false;
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
            Select::make('parent_id')->relationship('parent', 'name')->searchable(),
            TextInput::make('level')->numeric()->required(),
            TextInput::make('code')->maxLength(255),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255),
            TextInput::make('population')->numeric(),
            TextInput::make('area_km2')->numeric(),
            TextInput::make('path')->disabled()->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country.name')->label('Pays')->sortable(),
                TextColumn::make('level')->sortable(),
                TextColumn::make('parent.name')->label('Parent')->toggleable(),
                TextColumn::make('population')->numeric()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('country')->relationship('country', 'name')->searchable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminDivisions::route('/'),
            'create' => Pages\CreateAdminDivision::route('/create'),
            'edit' => Pages\EditAdminDivision::route('/{record}/edit'),
        ];
    }
}
