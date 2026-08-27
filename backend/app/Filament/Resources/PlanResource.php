<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Billing\Enums\BillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Enums\PermissionName;
use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Facturation';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::BillingManage->value) ?? false;
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
            TextInput::make('price_cents')->numeric()->required(),
            TextInput::make('currency')->required()->maxLength(3),
            Select::make('billing_period')->options(EnumOptions::for(BillingPeriod::class))->required(),
            KeyValue::make('features')->columnSpanFull(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('price_cents')->label('Prix (¢)')->numeric()->sortable(),
                TextColumn::make('billing_period')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
