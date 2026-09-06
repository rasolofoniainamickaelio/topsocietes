<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Content\Enums\ContentStatus;
use App\Enums\PermissionName;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Action "Publier" partagée par les ressources de contenu mutualisé (ville,
 * quartier, division administrative, activité, croisé ville×activité) :
 * transition explicite et tracée (`status` + `published_at` ensemble),
 * plutôt qu'un simple edit du champ `status` qui pourrait oublier de fixer
 * la date (Phase 08).
 */
class PublishContentAction
{
    public static function make(): Action
    {
        return Action::make('publish')
            ->label('Publier')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Model $record): bool => $record->getAttribute('status') !== ContentStatus::Published
                && (auth()->user()?->can(PermissionName::ContentManage->value) ?? false))
            ->requiresConfirmation()
            ->action(fn (Model $record) => $record->update([
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ]));
    }
}
