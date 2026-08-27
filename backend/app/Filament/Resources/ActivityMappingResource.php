<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Taxonomy\Models\ActivityMapping;
use App\Enums\PermissionName;
use App\Filament\Resources\ActivityMappingResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityMappingResource extends Resource
{
    protected static ?string $model = ActivityMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

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
            Select::make('from_activity_id')->relationship('fromActivity', 'public_label')->searchable()->required(),
            Select::make('to_activity_id')->relationship('toActivity', 'public_label')->searchable()->required(),
            TextInput::make('confidence')->numeric()->minValue(0)->maxValue(100)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromActivity.public_label')->label('Depuis'),
                TextColumn::make('toActivity.public_label')->label('Vers'),
                TextColumn::make('confidence')->numeric()->sortable(),
            ])
            ->defaultSort('confidence', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityMappings::route('/'),
            'create' => Pages\CreateActivityMapping::route('/create'),
            'edit' => Pages\EditActivityMapping::route('/{record}/edit'),
        ];
    }
}
