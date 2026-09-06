<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Billing\Actions\SetSubscriptionStatusAction;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Enums\PermissionName;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Facturation';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::BillingView->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.legal_name')->label('Entreprise')->searchable()->sortable(),
                TextColumn::make('plan.name')->label('Plan')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('current_period_end')->dateTime()->sortable(),
                IconColumn::make('auto_renew')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(SubscriptionStatus::class)),
            ])
            ->actions([
                Action::make('changeStatus')
                    ->label('Changer le statut')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->visible(fn (): bool => auth()->user()?->can(PermissionName::BillingManage->value) ?? false)
                    ->form([
                        Select::make('status')->options(EnumOptions::for(SubscriptionStatus::class))->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Un passage à "Actif" démasque les coordonnées, un passage à tout autre statut les remasque — de façon forcée, non affectée par le cycle automatique.')
                    ->action(function (Subscription $record, array $data, SetSubscriptionStatusAction $action): void {
                        $action->execute($record, SubscriptionStatus::from($data['status']), auth()->user());
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
        ];
    }
}
