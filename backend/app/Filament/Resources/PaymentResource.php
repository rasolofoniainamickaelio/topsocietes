<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Billing\Enums\PaymentStatus;
use App\Domain\Billing\Models\Payment;
use App\Enums\PermissionName;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

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
                TextColumn::make('subscription.company.legal_name')->label('Entreprise')->searchable(),
                TextColumn::make('provider')->badge(),
                TextColumn::make('amount_cents')->label('Montant (¢)')->numeric()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(PaymentStatus::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
