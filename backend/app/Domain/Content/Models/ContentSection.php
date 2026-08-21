<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ContentSectionScope;
use Database\Factories\ContentSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Table de référence (voir migration). Distincte de l'enum
 * `App\Domain\Content\Enums\ContentSection`, qui liste des constantes de clés connues
 * sans être le type casté d'aucune colonne.
 */
class ContentSection extends Model
{
    /** @use HasFactory<ContentSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'scope',
        'is_enabled',
        'min_facts_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ContentSectionScope::class,
            'is_enabled' => 'boolean',
            'min_facts_required' => 'integer',
            'display_order' => 'integer',
        ];
    }
}
