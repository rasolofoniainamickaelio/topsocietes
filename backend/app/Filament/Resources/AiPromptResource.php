<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSectionScope;
use App\Enums\PermissionName;
use App\Filament\Resources\AiPromptResource\Pages;
use App\Filament\Support\EnumOptions;
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

class AiPromptResource extends Resource
{
    protected static ?string $model = AiPrompt::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'IA';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::AiPipelineManage->value) ?? false;
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
            TextInput::make('version')->required()->maxLength(255),
            Select::make('scope')->options(EnumOptions::for(ContentSectionScope::class))->required(),
            Textarea::make('system_prompt')->required()->columnSpanFull(),
            Textarea::make('user_template')->required()->columnSpanFull(),
            TextInput::make('model')->required()->maxLength(255),
            Textarea::make('parameters')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('version'),
                TextColumn::make('scope')->badge(),
                TextColumn::make('model'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiPrompts::route('/'),
            'create' => Pages\CreateAiPrompt::route('/create'),
            'edit' => Pages\EditAiPrompt::route('/{record}/edit'),
        ];
    }
}
