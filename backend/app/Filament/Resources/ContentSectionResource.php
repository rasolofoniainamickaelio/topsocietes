<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Content\Enums\ContentSectionScope;
use App\Domain\Content\Models\ContentSection;
use App\Enums\PermissionName;
use App\Filament\Resources\ContentSectionResource\Pages;
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

class ContentSectionResource extends Resource
{
    protected static ?string $model = ContentSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contenus';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::ContentManage->value) ?? false;
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
            TextInput::make('key')->required()->maxLength(255),
            TextInput::make('label')->required()->maxLength(255),
            Select::make('scope')->options(EnumOptions::for(ContentSectionScope::class))->required(),
            Toggle::make('is_enabled')->default(true),
            TextInput::make('min_facts_required')->numeric()->required()->default(0),
            TextInput::make('display_order')->numeric()->required()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('scope')->badge(),
                TextColumn::make('display_order')->sortable(),
                IconColumn::make('is_enabled')->boolean(),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentSections::route('/'),
            'create' => Pages\CreateContentSection::route('/create'),
            'edit' => Pages\EditContentSection::route('/{record}/edit'),
        ];
    }
}
