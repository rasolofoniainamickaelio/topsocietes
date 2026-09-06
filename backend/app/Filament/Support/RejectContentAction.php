<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Content\Enums\ContentStatus;
use App\Enums\PermissionName;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Confirme qu'un contenu signalé douteux (`ContentStatus::Review`, Phase 11)
 * est effectivement problématique — jamais publiable en l'état. Capture
 * l'identité du relecteur, même patron que `ApproveContentAction`.
 */
class RejectContentAction
{
    public static function make(): Action
    {
        return Action::make('rejectReview')
            ->label('Rejeter')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Model $record): bool => $record->getAttribute('status') === ContentStatus::Review
                && (auth()->user()?->can(PermissionName::ContentManage->value) ?? false))
            ->requiresConfirmation()
            ->action(fn (Model $record) => $record->update([
                'status' => ContentStatus::Rejected,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]));
    }
}
