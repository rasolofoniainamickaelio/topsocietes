<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';
}
