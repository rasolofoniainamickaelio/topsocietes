<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Jobs\GenerateSitemapsJob;
use Illuminate\Console\Command;

class GenerateSitemapsCommand extends Command
{
    protected $signature = 'sitemaps:generate {country? : Sous-domaine du pays (sinon tous les pays actifs)}';

    protected $description = 'Met en file la (re)génération des sitemaps segmentés pour un pays ou tous les pays actifs';

    public function handle(): int
    {
        $countries = $this->argument('country') !== null
            ? Country::query()->where('subdomain', $this->argument('country'))->where('is_active', true)->get()
            : Country::query()->where('is_active', true)->get();

        if ($countries->isEmpty()) {
            $this->error('Aucun pays actif correspondant.');

            return self::FAILURE;
        }

        $countries->each(fn (Country $country) => GenerateSitemapsJob::dispatch($country));

        $this->info("{$countries->count()} pays mis en file pour régénération des sitemaps.");

        return self::SUCCESS;
    }
}
