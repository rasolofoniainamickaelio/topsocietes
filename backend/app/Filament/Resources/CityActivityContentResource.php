<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Content\Enums\ContentSection as ContentSectionEnum;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\CityActivityContent;
use App\Enums\PermissionName;
use App\Filament\Resources\CityActivityContentResource\Pages;
use App\Filament\Support\ApproveContentAction;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\PublishContentAction;
use App\Filament\Support\RejectContentAction;
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

class CityActivityContentResource extends Resource
{
    protected static ?string $model = CityActivityContent::class;

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
            Select::make('city_id')->relationship('city', 'name')->searchable()->required(),
            Select::make('activity_id')->relationship('activity', 'public_label')->searchable()
                ->helperText('Exclusif avec Secteur — un seul des deux doit être renseigné.'),
            Select::make('sector_id')->relationship('sector', 'name')->searchable(),
            TextInput::make('locale')->required()->maxLength(10)->default('fr'),
            Select::make('section')->options(EnumOptions::for(ContentSectionEnum::class))->required(),
            Textarea::make('body')->columnSpanFull(),
            Select::make('status')->options(EnumOptions::for(ContentStatus::class))->required(),
            TextInput::make('companies_count_at_generation')->numeric()->disabled(),
            DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city.name')->label('Ville')->searchable()->sortable(),
                TextColumn::make('activity.public_label')->label('Activité')->searchable(),
                TextColumn::make('sector.name')->label('Secteur')->searchable(),
                TextColumn::make('section')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnumOptions::for(ContentStatus::class)),
            ])
            ->actions([PublishContentAction::make(), ApproveContentAction::make(), RejectContentAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCityActivityContents::route('/'),
            'create' => Pages\CreateCityActivityContent::route('/create'),
            'edit' => Pages\EditCityActivityContent::route('/{record}/edit'),
        ];
    }
}
