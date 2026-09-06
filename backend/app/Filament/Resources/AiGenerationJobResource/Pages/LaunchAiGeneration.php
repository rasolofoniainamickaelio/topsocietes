<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiGenerationJobResource\Pages;

use App\Domain\Ai\Jobs\GenerateActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityActivityContentJob;
use App\Domain\Ai\Jobs\GenerateCityContentJob;
use App\Domain\Ai\Models\AiGenerationJob;
use App\Domain\Content\Enums\ContentSection;
use App\Domain\Geo\Models\City;
use App\Domain\Geo\Models\Country;
use App\Domain\Taxonomy\Models\Activity;
use App\Domain\Taxonomy\Models\Sector;
use App\Filament\Resources\AiGenerationJobResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Lance une génération IA depuis le back-office (Phase 21) — jusqu'ici
 * possible uniquement en CLI. Ne crée jamais de ligne `AiGenerationJob`
 * elle-même : `handleRecordCreation()` se contente de mettre en file le Job
 * correspondant, toujours en file (CLAUDE.md §6.1), jamais un appel
 * synchrone au fournisseur IA depuis cette requête HTTP. La ligne
 * `AiGenerationJob` réelle n'existe qu'une fois le Job exécuté par un
 * worker — cette page redirige donc vers la liste, jamais vers une fiche.
 */
class LaunchAiGeneration extends CreateRecord
{
    protected static string $resource = AiGenerationJobResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('target')
                ->label('Cible')
                ->options([
                    'city' => 'Commune',
                    'activity_or_sector' => 'Activité ou secteur',
                    'city_activity' => 'Croisé commune × activité/secteur',
                ])
                ->required()
                ->live(),
            Select::make('country_id')
                ->label('Pays')
                ->relationship('country', 'name', fn ($query) => $query->where('is_active', true))
                ->helperText('Requis pour cibler une commune. Optionnel pour un contenu activité/secteur : laisser vide pour un contenu générique (tous pays).')
                ->visible(fn ($get) => in_array($get('target'), ['city', 'city_activity'], true)),
            Select::make('city_id')
                ->label('Commune')
                ->relationship('city', 'name')
                ->searchable()
                ->required()
                ->visible(fn ($get) => in_array($get('target'), ['city', 'city_activity'], true)),
            Select::make('subject_kind')
                ->label('Type de sujet')
                ->options(['activity' => 'Activité', 'sector' => 'Secteur'])
                ->required()
                ->live()
                ->visible(fn ($get) => in_array($get('target'), ['activity_or_sector', 'city_activity'], true)),
            Select::make('activity_id')
                ->label('Activité')
                ->relationship('activity', 'public_label')
                ->searchable()
                ->required()
                ->visible(fn ($get) => in_array($get('target'), ['activity_or_sector', 'city_activity'], true) && $get('subject_kind') === 'activity'),
            Select::make('sector_id')
                ->label('Secteur')
                ->relationship('sector', 'name')
                ->searchable()
                ->required()
                ->visible(fn ($get) => in_array($get('target'), ['activity_or_sector', 'city_activity'], true) && $get('subject_kind') === 'sector'),
            Select::make('section')
                ->label('Section')
                ->options(fn ($get) => self::sectionOptions((string) $get('target')))
                ->required(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function sectionOptions(string $target): array
    {
        $sections = match ($target) {
            'city' => [ContentSection::History, ContentSection::Nature, ContentSection::Leisure, ContentSection::Specialty, ContentSection::Stats, ContentSection::Faq],
            'activity_or_sector' => [ContentSection::UnderstandingSector, ContentSection::HowItWorks, ContentSection::Jobs, ContentSection::Diplomas, ContentSection::Regulation, ContentSection::CommonMistakes, ContentSection::HowToChoose, ContentSection::BusinessCreation, ContentSection::Faq],
            'city_activity' => [ContentSection::LocalOverview, ContentSection::LocalHistory, ContentSection::LocalSpecifics, ContentSection::LocalEconomy, ContentSection::Faq],
            default => [],
        };

        return collect($sections)->mapWithKeys(fn (ContentSection $section) => [$section->value => str($section->name)->headline()->toString()])->all();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $section = ContentSection::from($data['section']);

        match ($data['target']) {
            'city' => GenerateCityContentJob::dispatch(
                City::query()->findOrFail($data['city_id']),
                $section,
            ),
            'activity_or_sector' => GenerateActivityContentJob::dispatch(
                $this->resolveSubject($data),
                filled($data['country_id'] ?? null) ? Country::query()->find($data['country_id']) : null,
                $section,
            ),
            'city_activity' => GenerateCityActivityContentJob::dispatch(
                City::query()->findOrFail($data['city_id']),
                $this->resolveSubject($data),
                $section,
            ),
            default => throw new InvalidArgumentException("Cible inconnue : {$data['target']}"),
        };

        Notification::make()->title('Génération mise en file')->success()->send();

        return new AiGenerationJob;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSubject(array $data): Activity|Sector
    {
        return $data['subject_kind'] === 'activity'
            ? Activity::query()->findOrFail($data['activity_id'])
            : Sector::query()->findOrFail($data['sector_id']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
