<?php

declare(strict_types=1);

namespace App\Domain\Company\Actions;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyClaim;
use App\Domain\Company\Models\CompanyContact;
use App\Domain\Company\Models\Establishment;
use App\Domain\Seo\Actions\CreateRedirectAction;
use App\Domain\Seo\Enums\RedirectReason;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fusionne un doublon (`$source`) dans la fiche canonique (`$target`)
 * (Phase 21) : transfère tout ce qui a de la valeur (établissements,
 * contacts, revendications, abonnements) puis supprime la source en
 * soft-delete — jamais en dur, pour permettre un retour arrière en cas
 * d'erreur de fusion. Crée systématiquement une redirection permanente de
 * l'ancien chemin vers le nouveau, pour ne jamais transformer une fiche
 * fusionnée en 404 silencieuse (CLAUDE.md §6.4, même mécanisme que M8).
 */
class MergeCompaniesAction
{
    public function __construct(
        private readonly BuildCompanyPathAction $buildPath,
        private readonly CreateRedirectAction $createRedirect,
    ) {}

    public function execute(Company $source, Company $target): Company
    {
        if ($source->is($target)) {
            throw new InvalidArgumentException('Impossible de fusionner une fiche avec elle-même.');
        }

        if ($source->country_id !== $target->country_id) {
            throw new InvalidArgumentException('Impossible de fusionner deux fiches de pays différents.');
        }

        $sourcePath = $this->buildPath->execute($source);
        $targetPath = $this->buildPath->execute($target);

        DB::transaction(function () use ($source, $target): void {
            Establishment::query()->where('company_id', $source->id)->get()
                ->each(fn (Establishment $establishment) => $establishment->update(['company_id' => $target->id]));

            CompanyContact::query()->where('company_id', $source->id)->get()
                ->each(fn (CompanyContact $contact) => $contact->update(['company_id' => $target->id]));

            CompanyClaim::query()->where('company_id', $source->id)->get()
                ->each(fn (CompanyClaim $claim) => $claim->update(['company_id' => $target->id]));

            Subscription::query()->where('company_id', $source->id)->get()
                ->each(fn (Subscription $subscription) => $subscription->update(['company_id' => $target->id]));

            $source->delete();
        });

        $this->createRedirect->execute($target->country, $sourcePath, $targetPath, RedirectReason::Merge);

        return $target->fresh();
    }
}
