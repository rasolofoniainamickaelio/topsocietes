<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ads\Enums\AdDevice;
use App\Domain\Ads\Models\AdSlot;
use App\Enums\PermissionName;
use App\Filament\Resources\AdSlotResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdSlotResource extends Resource
{
    protected static ?string $model = AdSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

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
            TextInput::make('code')->required()->maxLength(255),
            TextInput::make('label')->required()->maxLength(255),
            Select::make('device')->options(EnumOptions::for(AdDevice::class))->required(),
            TextInput::make('position')->numeric()->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('device')->badge(),
                TextColumn::make('position')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdSlots::route('/'),
            'create' => Pages\CreateAdSlot::route('/create'),
            'edit' => Pages\EditAdSlot::route('/{record}/edit'),
        ];
    }
}
