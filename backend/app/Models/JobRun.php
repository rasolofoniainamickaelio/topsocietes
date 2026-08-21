<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JobRunStatus;
use Database\Factories\JobRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRun extends Model
{
    /** @use HasFactory<JobRunFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'queue',
        'status',
        'payload',
        'output',
        'exception',
        'attempts',
        'progress_current',
        'progress_total',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobRunStatus::class,
            'payload' => 'array',
            'output' => 'array',
            'attempts' => 'integer',
            'progress_current' => 'integer',
            'progress_total' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }
}
