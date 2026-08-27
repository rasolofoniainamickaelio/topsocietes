<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Seo\Enums\PageType;
use App\Domain\Seo\Models\PagePublicationRule;
use App\Enums\PermissionName;
use App\Filament\Resources\PagePublicationRuleResource\Pages;
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

class PagePublicationRuleResource extends Resource
{
    protected static ?string $model = PagePublicationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

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
            Select::make('page_type')->options(EnumOptions::for(PageType::class))->required(),
            Select::make('country_id')->relationship('country', 'name')->searchable()
                ->helperText('Laisser vide pour une règle globale par défaut.'),
            TextInput::make('min_companies')->numeric()->default(0),
            TextInput::make('min_facts')->numeric()->default(0),
            TextInput::make('min_content_sections')->numeric()->default(0),
            TextInput::make('min_word_count')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page_type')->badge()->sortable(),
                TextColumn::make('country.name')->label('Pays')->toggleable(),
                TextColumn::make('min_companies')->label('Min. entreprises'),
                TextColumn::make('min_facts')->label('Min. faits'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('page_type');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagePublicationRules::route('/'),
            'create' => Pages\CreatePagePublicationRule::route('/create'),
            'edit' => Pages\EditPagePublicationRule::route('/{record}/edit'),
        ];
    }
}
