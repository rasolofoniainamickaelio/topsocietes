<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Exécute réellement `backup:run` (dump pgsql + fichiers) plutôt que de
 * tester la seule forme de la config — preuve fonctionnelle de bout en
 * bout que pg_dump est joignable et que l'archive est produite sur le
 * disque configuré.
 */
it('produces a real backup archive on the configured disk', function (): void {
    $disk = Storage::disk('backups');
    $before = collect($disk->allFiles())->filter(fn (string $path): bool => str_ends_with($path, '.zip'));

    $exitCode = Artisan::call('backup:run', ['--only-to-disk' => 'backups']);

    expect($exitCode)->toBe(0);

    $after = collect($disk->allFiles())->filter(fn (string $path): bool => str_ends_with($path, '.zip'));

    expect($after->count())->toBeGreaterThan($before->count());
});
