<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Seo\Enums\RedirectReason;
use App\Domain\Seo\Models\Redirect;
use App\Enums\PermissionName;
use App\Filament\Resources\RedirectResource\Pages;
use App\Filament\Support\EnumOptions;
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

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?string $navigationGroup = 'SEO';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::SeoManage->value) ?? false;
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
            TextInput::make('from_path')->required()->maxLength(255),
            TextInput::make('to_path')->required()->maxLength(255),
            TextInput::make('status_code')->numeric()->required()->default(301),
            Select::make('reason')->options(EnumOptions::for(RedirectReason::class))->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->searchable()->sortable(),
                TextColumn::make('to_path')->searchable(),
                TextColumn::make('status_code'),
                TextColumn::make('reason')->badge(),
                TextColumn::make('hit_count')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('reason')->options(EnumOptions::for(RedirectReason::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
