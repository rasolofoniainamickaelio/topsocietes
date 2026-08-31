<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Ai\Models\AiPrompt;
use App\Domain\Content\Enums\ContentSectionScope;
use Illuminate\Database\Seeder;

/**
 * Prompt actif pour la génération de contenu ville (Phases 10-11). Le
 * prompt système porte toute la règle anti-hallucination du CLAUDE.md
 * §6.7 : jamais de sujet libre, uniquement les faits fournis, sentinel
 * `INSUFFICIENT_DATA` si le sujet ne peut pas être traité honnêtement.
 */
class AiPromptSeeder extends Seeder
{
    public function run(): void
    {
        AiPrompt::query()->updateOrCreate(
            ['key' => 'city_content', 'version' => 'v1'],
            [
                'scope' => ContentSectionScope::City,
                'system_prompt' => <<<'PROMPT'
                    Tu rédiges du contenu éditorial pour un annuaire d'entreprises français, à partir UNIQUEMENT des faits fournis dans le message utilisateur.

                    Règles absolues :
                    - N'invente JAMAIS un chiffre, un nom, une date ou une affirmation qui n'est pas explicitement présent dans les faits fournis.
                    - N'aborde JAMAIS, même si on te le demande : chiffre d'affaires ou autres chiffres financiers, solvabilité, litiges ou contentieux, certifications, avis clients, réputation, effectifs.
                    - Reste factuel et sourcé : si un fait n'est pas dans la liste fournie, tu ne le mentionnes pas.
                    - Si les faits fournis sont insuffisants pour rédiger un contenu utile et honnête, réponds EXACTEMENT et UNIQUEMENT le texte : INSUFFICIENT_DATA (rien d'autre, aucune explication).

                    Style : deux à quatre phrases, ton neutre et informatif, casse phrase, aucune capitale intégrale.
                    PROMPT,
                'user_template' => <<<'PROMPT'
                    Ville : {{city}}
                    Section à rédiger : {{section}}

                    Faits disponibles :
                    {{facts}}

                    Rédige le contenu de cette section à partir de ces seuls faits.
                    PROMPT,
                'model' => (string) config('services.ai.model', 'gpt-4o-mini'),
                'parameters' => [],
                'is_active' => true,
            ],
        );
    }
}
