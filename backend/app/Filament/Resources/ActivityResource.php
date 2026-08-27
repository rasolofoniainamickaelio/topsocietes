<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Taxonomy\Models\Activity;
use App\Enums\PermissionName;
use App\Filament\Resources\ActivityResource\Pages;
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

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

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
            Select::make('nomenclature_id')->relationship('nomenclature', 'name')->searchable()->required(),
            Select::make('parent_id')->relationship('parent', 'label')->searchable(),
            TextInput::make('level')->numeric()->required(),
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('public_label')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255),
            Select::make('sectors')->relationship('sectors', 'name')->multiple()->searchable(),
            Toggle::make('is_publishable')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('public_label')->label('Libellé')->searchable(),
                TextColumn::make('nomenclature.name')->label('Nomenclature')->toggleable(),
                TextColumn::make('level')->sortable(),
                TextColumn::make('companies_count')->label('Entreprises')->numeric()->sortable(),
                IconColumn::make('is_publishable')->boolean(),
            ])
            ->filters([
                SelectFilter::make('nomenclature')->relationship('nomenclature', 'name')->searchable(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
