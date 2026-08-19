<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClaimVerificationMethod;
use App\Enums\CompanyClaimStatus;
use Database\Factories\CompanyClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyClaim extends Model
{
    /** @use HasFactory<CompanyClaimFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'status',
        'verification_method',
        'evidence_path',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyClaimStatus::class,
            'verification_method' => ClaimVerificationMethod::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
