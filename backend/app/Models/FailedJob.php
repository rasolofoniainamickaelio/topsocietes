<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modélise `failed_jobs` (native Laravel, alimentée automatiquement par le
 * worker de queue) — jamais peuplée par du code applicatif, seulement lue
 * ici pour le visualiseur d'erreurs du back-office (Phase 21).
 */
class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $casts = [
        'failed_at' => 'datetime',
    ];
}
