<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Domain\Geo\Models\Country;
use App\Domain\Import\Actions\StartImportBatchAction;
use App\Domain\Import\Enums\ImportFormat;
use App\Domain\Import\Models\ImportMapping;
use App\Filament\Resources\ImportBatchResource;
use App\Filament\Support\EnumOptions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Lance un import depuis le back-office (Phase 21) — jusqu'ici possible
 * uniquement en CLI (`import:companies`). `handleRecordCreation()` délègue
 * entièrement à `StartImportBatchAction` : cette page ne fait que collecter
 * le fichier et les paramètres, jamais de logique d'import elle-même
 * (CLAUDE.md §3, "un contrôleur orchestre, il ne décide pas").
 */
class StartImportBatch extends CreateRecord
{
    protected static string $resource = ImportBatchResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('country_id')
                ->label('Pays')
                ->relationship('country', 'name', fn ($query) => $query->where('is_active', true))
                ->required()
                ->live()
                ->afterStateUpdated(fn ($set) => $set('mapping_id', null)),
            Select::make('format')
                ->label('Format')
                ->options(EnumOptions::for(ImportFormat::class))
                ->required(),
            Select::make('mapping_id')
                ->label('Mapping de colonnes')
                ->options(fn ($get) => ImportMapping::query()
                    ->where('country_id', $get('country_id'))
                    ->pluck('name', 'id'))
                ->required()
                ->helperText('Le mapping détermine la correspondance entre les colonnes du fichier et les champs de la fiche entreprise.'),
            FileUpload::make('file')
                ->label('Fichier')
                ->disk('local')
                ->directory('imports')
                ->required()
                ->acceptedFileTypes(['text/csv', 'application/json', 'text/xml', 'application/xml']),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $country = Country::query()->findOrFail($data['country_id']);
        $mapping = ImportMapping::query()->findOrFail($data['mapping_id']);
        $format = ImportFormat::from($data['format']);

        return app(StartImportBatchAction::class)->execute(
            $country,
            (string) $data['file'],
            $format,
            $mapping,
            auth()->user(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
