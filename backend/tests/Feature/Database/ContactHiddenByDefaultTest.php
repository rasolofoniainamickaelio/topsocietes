<?php

declare(strict_types=1);

use App\Domain\Company\Enums\ContactVisibility;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\CompanyContact;
use Illuminate\Support\Facades\DB;

/**
 * `visibility` doit être masqué par défaut au niveau base — garantie
 * structurelle indépendante de toute Action applicative (CLAUDE.md §6.3) :
 * insertion volontairement en SQL brut, sans passer par la valeur par
 * défaut du modèle Eloquent, pour prouver que c'est bien la colonne qui
 * protège, pas seulement la couche PHP.
 */
it('defaults a contact to hidden visibility at the database level', function (): void {
    $company = Company::factory()->create();

    $id = DB::table('company_contacts')->insertGetId([
        'company_id' => $company->id,
        'type' => 'phone',
        'value' => '+33100000000',
        'source' => 'import',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $contact = CompanyContact::query()->findOrFail($id);

    expect($contact->visibility)->toBe(ContactVisibility::Hidden);
});
