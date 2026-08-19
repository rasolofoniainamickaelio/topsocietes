<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ImportErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne en erreur à l'import. `is_resolved`/`retried_at` permettent de
 * ne relancer que les lignes en échec, jamais tout le lot.
 */
class ImportError extends Model
{
    /** @use HasFactory<ImportErrorFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'row_number',
        'raw_row',
        'error_code',
        'error_message',
        'is_resolved',
        'retried_at',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'raw_row' => 'array',
            'is_resolved' => 'boolean',
            'retried_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}
