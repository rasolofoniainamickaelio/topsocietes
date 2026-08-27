<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Content\Enums\ContentSection as ContentSectionEnum;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\DistrictContent;
use App\Enums\PermissionName;
use App\Filament\Resources\DistrictContentResource\Pages;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DistrictContentResource extends Resource
{
    protected static ?string $model = DistrictContent::class;

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
            Select::make('district_id')->relationship('district', 'name')->searchable()->required(),
            TextInput::make('locale')->required()->maxLength(10)->default('fr'),
            Select::make('section')->options(EnumOptions::for(ContentSectionEnum::class))->required(),
            TextInput::make('title')->maxLength(255),
            Textarea::make('body')->columnSpanFull(),
            Select::make('status')->options(EnumOptions::for(ContentStatus::class))->required(),
            TextInput::make('quality_score')->numeric()->minValue(0)->maxValue(100),
            DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('district.name')->label('Quartier')->searchable()->sortable(),
                TextColumn::make('section')->badge(),
                TextColumn::make('title')->limit(50),
                TextColumn::make('status')->badge(),
                TextColumn::make('quality_score')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(ContentStatus::class)),
                SelectFilter::make('section')->options(EnumOptions::for(ContentSectionEnum::class)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistrictContents::route('/'),
            'create' => Pages\CreateDistrictContent::route('/create'),
            'edit' => Pages\EditDistrictContent::route('/{record}/edit'),
        ];
    }
}
