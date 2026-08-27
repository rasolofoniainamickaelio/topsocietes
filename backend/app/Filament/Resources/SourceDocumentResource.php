<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Content\Models\SourceDocument;
use App\Enums\PermissionName;
use App\Filament\Resources\SourceDocumentResource\Pages;
use App\Filament\Resources\SourceDocumentResource\RelationManagers\LinksRelationManager;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SourceDocumentResource extends Resource
{
    protected static ?string $model = SourceDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Contenus';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionName::SourcesManage->value) ?? false;
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
            Select::make('source_id')->relationship('source', 'name')->searchable()->required(),
            TextInput::make('subject_type')->required()->maxLength(255)
                ->helperText('Alias court : city, district, activity, poi, company…'),
            TextInput::make('subject_id')->numeric()->required(),
            TextInput::make('url')->url()->maxLength(255),
            Textarea::make('raw_payload')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            Textarea::make('normalized_payload')
                ->formatStateUsing(fn (?array $state) => $state !== null ? json_encode($state, JSON_PRETTY_PRINT) : null)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                ->helperText('JSON')
                ->columnSpanFull(),
            DateTimePicker::make('fetched_at'),
            TextInput::make('hash')->disabled()->dehydrated(false),
            TextInput::make('http_status')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')->label('Source')->searchable()->sortable(),
                TextColumn::make('subject_type')->label('Sujet'),
                TextColumn::make('subject_id')->label('ID sujet'),
                TextColumn::make('http_status')->label('HTTP'),
                TextColumn::make('fetched_at')->dateTime()->sortable(),
            ])
            ->defaultSort('fetched_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSourceDocuments::route('/'),
            'create' => Pages\CreateSourceDocument::route('/create'),
            'edit' => Pages\EditSourceDocument::route('/{record}/edit'),
        ];
    }
}
