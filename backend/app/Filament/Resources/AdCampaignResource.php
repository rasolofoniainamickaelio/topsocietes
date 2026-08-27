<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ads\Models\AdCampaign;
use App\Enums\PermissionName;
use App\Filament\Resources\AdCampaignResource\Pages;
use Filament\Forms\Components\DateTimePicker;
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

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

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
            Select::make('slot_id')->relationship('slot', 'label')->searchable()->required(),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('body')->columnSpanFull(),
            TextInput::make('cta_label')->maxLength(255),
            TextInput::make('cta_url')->url()->maxLength(255),
            TextInput::make('theme')->maxLength(255),
            DateTimePicker::make('starts_at'),
            DateTimePicker::make('ends_at'),
            TextInput::make('weight')->numeric()->default(1),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slot.label')->label('Emplacement')->sortable(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('impressions_count')->label('Impressions')->numeric()->sortable(),
                TextColumn::make('clicks_count')->label('Clics')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdCampaigns::route('/'),
            'create' => Pages\CreateAdCampaign::route('/create'),
            'edit' => Pages\EditAdCampaign::route('/{record}/edit'),
        ];
    }
}
