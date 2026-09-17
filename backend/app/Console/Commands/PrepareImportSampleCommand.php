<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Découpe un CSV source en échantillon borné pour le test industriel
 * Phase 19 (10k–50k). L'import n'accepte pas le xlsx : exporter
 * `docs/MAROC_ENTREPRISE.xlsx` en CSV UTF-8 avant d'appeler cette commande.
 */
class PrepareImportSampleCommand extends Command
{
    protected $signature = 'import:prepare-sample
        {source : Chemin absolu ou relatif (repo) du CSV source}
        {--limit=10000 : Nombre de lignes de données à conserver (hors en-tête)}
        {--output= : Chemin relatif au disque local (défaut : imports/samples/...)}';

    protected $description = 'Produit un CSV d\'échantillon borné pour un test d\'import industriel';

    public function handle(): int
    {
        $source = (string) $this->argument('source');
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error('--limit doit être un entier ≥ 1.');

            return self::FAILURE;
        }

        if (! is_file($source)) {
            $fromBase = base_path($source);
            $fromRoot = dirname(base_path()).DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $source), DIRECTORY_SEPARATOR);

            if (is_file($fromBase)) {
                $source = $fromBase;
            } elseif (is_file($fromRoot)) {
                $source = $fromRoot;
            } else {
                // Chemin relatif au monorepo (docs/... depuis backend/)
                $repoDocs = dirname(base_path()).DIRECTORY_SEPARATOR.$source;
                if (is_file($repoDocs)) {
                    $source = $repoDocs;
                } else {
                    $this->error("Fichier source introuvable : {$this->argument('source')}");

                    return self::FAILURE;
                }
            }
        }

        if (str_ends_with(mb_strtolower($source), '.xlsx')) {
            $this->error('Le format xlsx n\'est pas supporté. Exportez d\'abord en CSV UTF-8 (Excel / LibreOffice), puis relancez.');

            return self::FAILURE;
        }

        $output = $this->option('output') ?: 'imports/samples/sample-'.date('Ymd-His').'-'.$limit.'.csv';

        $in = fopen($source, 'rb');

        if ($in === false) {
            $this->error("Impossible d'ouvrir le fichier source.");

            return self::FAILURE;
        }

        Storage::disk('local')->makeDirectory(dirname($output));
        $absoluteOut = Storage::disk('local')->path($output);
        $out = fopen($absoluteOut, 'wb');

        if ($out === false) {
            fclose($in);
            $this->error("Impossible d'écrire {$output}.");

            return self::FAILURE;
        }

        $written = 0;

        try {
            $header = fgets($in);

            if ($header === false) {
                $this->error('Fichier CSV vide.');

                return self::FAILURE;
            }

            fwrite($out, $header);

            while (($line = fgets($in)) !== false && $written < $limit) {
                if (trim($line) === '') {
                    continue;
                }

                fwrite($out, $line);
                $written++;
            }
        } finally {
            fclose($in);
            fclose($out);
        }

        $this->info("Échantillon écrit : {$output} ({$written} ligne(s) de données).");

        return self::SUCCESS;
    }
}
