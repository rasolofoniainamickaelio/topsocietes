<?php

declare(strict_types=1);

namespace App\Domain\Seo\Models;

use App\Domain\Geo\Models\Country;
use App\Domain\Seo\Enums\PageType;
use Database\Factories\PagePublicationRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagePublicationRule extends Model
{
    /** @use HasFactory<PagePublicationRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'page_type',
        'country_id',
        'min_companies',
        'min_facts',
        'min_content_sections',
        'min_word_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'page_type' => PageType::class,
            'min_companies' => 'integer',
            'min_facts' => 'integer',
            'min_content_sections' => 'integer',
            'min_word_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
