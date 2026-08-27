<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ads\Models\ServiceLink;
use App\Enums\PermissionName;
use App\Filament\Resources\ServiceLinkResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceLinkResource extends Resource
{
    protected static ?string $model = ServiceLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Publicité';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::AdsManage->value) ?? false;
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
            Select::make('country_id')->relationship('country', 'name')->searchable(),
            TextInput::make('group')->required()->maxLength(255),
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('url')->url()->required()->maxLength(255),
            TextInput::make('display_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('group')->badge(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('display_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceLinks::route('/'),
            'create' => Pages\CreateServiceLink::route('/create'),
            'edit' => Pages\EditServiceLink::route('/{record}/edit'),
        ];
    }
}
