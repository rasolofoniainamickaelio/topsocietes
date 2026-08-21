<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

/**
 * `Submitted` porte la valeur `'new'` du prompt : `New` est impossible
 * comme nom de cas PHP (mot-clé réservé, insensible à la casse pour le
 * lexer — `new`/`New`/`NEW` sont tous le même token).
 */
enum DisputeStatus: string
{
    case Submitted = 'new';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Applied = 'applied';
}
