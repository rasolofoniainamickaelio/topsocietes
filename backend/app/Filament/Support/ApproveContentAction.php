<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Content\Enums\ContentStatus;
use App\Enums\PermissionName;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Confirme qu'un contenu signalé douteux par le contrôle anti-hallucination
 * (`ContentStatus::Review`, Phase 11) est en réalité conforme. Repasse en
 * `Generated`, jamais directement `Published` : la publication reste un
 * geste distinct et explicite (voir `PublishContentAction`), même après
 * validation humaine. Capture l'identité du relecteur (`reviewed_by`/
 * `reviewed_at`) plutôt qu'un simple edit de champ sans traçabilité.
 */
class ApproveContentAction
{
    public static function make(): Action
    {
        return Action::make('approveReview')
            ->label('Approuver')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Model $record): bool => $record->getAttribute('status') === ContentStatus::Review
                && (auth()->user()?->can(PermissionName::ContentManage->value) ?? false))
            ->requiresConfirmation()
            ->modalDescription('Confirme que ce contenu signalé est conforme. Il repasse en attente de publication, sans être publié automatiquement.')
            ->action(fn (Model $record) => $record->update([
                'status' => ContentStatus::Generated,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]));
    }
}
