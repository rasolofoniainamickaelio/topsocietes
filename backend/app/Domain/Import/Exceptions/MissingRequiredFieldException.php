<?php

declare(strict_types=1);

namespace App\Domain\Import\Exceptions;

use RuntimeException;

/**
 * Ligne sans `national_id` ou `legal_name` après mapping — distincte d'une
 * erreur technique inattendue (`error_code` différent en base, voir
 * `ProcessImportChunkAction`).
 */
class MissingRequiredFieldException extends RuntimeException {}
