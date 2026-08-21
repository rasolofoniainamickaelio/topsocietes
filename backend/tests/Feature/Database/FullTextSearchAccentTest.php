<?php

declare(strict_types=1);

use App\Domain\Geo\Models\City;
use Illuminate\Support\Facades\DB;

/**
 * `search_vector` utilise la configuration `french_unaccent` (créée en
 * Domaine A) : une recherche sans accent et une recherche en majuscules
 * doivent toutes deux retrouver « Orléans ».
 */
it('finds an accented name via an unaccented, lowercase query', function (): void {
    City::factory()->create(['name' => 'Orléans']);

    $found = DB::table('cities')
        ->whereRaw("search_vector @@ plainto_tsquery('french_unaccent', ?)", ['orleans'])
        ->exists();

    expect($found)->toBeTrue();
});

it('finds an accented name via an uppercase query', function (): void {
    City::factory()->create(['name' => 'Orléans']);

    $found = DB::table('cities')
        ->whereRaw("search_vector @@ plainto_tsquery('french_unaccent', ?)", ['ORLEANS'])
        ->exists();

    expect($found)->toBeTrue();
});

it('does not match an unrelated term', function (): void {
    City::factory()->create(['name' => 'Orléans']);

    $found = DB::table('cities')
        ->whereRaw("search_vector @@ plainto_tsquery('french_unaccent', ?)", ['marseille'])
        ->exists();

    expect($found)->toBeFalse();
});
