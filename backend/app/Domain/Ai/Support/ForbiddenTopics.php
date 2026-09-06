<?php

declare(strict_types=1);

namespace App\Domain\Ai\Support;

/**
 * Liste noire du CLAUDE.md §6.7 : thèmes que l'IA ne doit jamais aborder,
 * même si les faits fournis les mentionnaient (ce qui ne devrait jamais
 * arriver côté contenu territorial, mais le contrôle reste défensif).
 *
 * Détection par mot-clé, pas sémantique — limite assumée : un texte peut
 * évoquer un thème interdit sans utiliser un de ces mots exacts, et à
 * l'inverse un mot peut apparaître dans un contexte inoffensif. C'est un
 * premier filet, pas une garantie absolue ; la revue humaine (statut
 * "à valider") reste la protection finale.
 */
final class ForbiddenTopics
{
    /**
     * Préfixée à CHAQUE prompt système avant l'appel au fournisseur — jamais
     * seulement présente dans l'`AiPrompt` éditable en back-office, pour
     * qu'aucune édition (même bien intentionnée) ne puisse faire disparaître
     * la consigne (CLAUDE.md §6.7). `AiPrompt.system_prompt` reste éditable
     * pour le TON et le CONTENU de la section, jamais pour cette garde.
     */
    public const SYSTEM_GUARDRAIL = <<<'PROMPT'
        Règles impératives, non négociables :
        - N'utilise QUE les faits fournis ci-dessous. N'invente jamais un chiffre, un nom, une date ou une affirmation absente de ces faits.
        - N'aborde JAMAIS : chiffre d'affaires, solvabilité, litiges, certifications, avis clients, effectifs, réputation, dirigeants, clients, historique d'une entreprise précise.
        - Si les faits fournis sont insuffisants pour rédiger ce contenu sans enfreindre ces règles, réponds exactement : INSUFFICIENT_DATA
        PROMPT;

    /** @var array<string, array<int, string>> */
    private const KEYWORDS = [
        'chiffres financiers' => ["chiffre d'affaires", 'chiffre daffaires', 'bénéfice', 'résultat net', 'rentabilité', 'marge bénéficiaire'],
        'solvabilité' => ['solvable', 'solvabilité', 'situation financière'],
        'litiges' => ['litige', 'procès', 'contentieux', 'condamné', 'condamnation', 'plainte'],
        'certifications' => ['certifié', 'certification', 'labellisé', 'accrédité', 'iso 9001'],
        'avis' => ['avis client', 'avis positif', 'avis négatif', 'note de satisfaction', 'étoiles'],
        'effectifs' => ['salarié', 'employés', "effectif de l'entreprise", 'collaborateurs de la société'],
        'réputation' => ['réputation', 'réputé', 'leader du marché', 'meilleure entreprise'],
        'dirigeants' => ['dirigeant', 'gérant de la société', 'directeur général', 'pdg', 'président de la société'],
        'clients' => ['ses clients', 'sa clientèle', 'parmi ses clients', 'travaille avec des clients'],
        // "Historique de l'entreprise" au sens du CLAUDE.md §6.7 : le récit
        // narratif d'UNE société (fondateur, décennies d'activité...), pas
        // l'histoire d'un territoire — seule cette dernière est générée par
        // ce pipeline pour l'instant (Phase 10, contenu communal).
        "historique de l'entreprise" => ["l'histoire de l'entreprise", "l'histoire de cette société", 'depuis sa fondation par', "au fil des décennies, l'entreprise"],
    ];

    /**
     * @return string|null Le thème détecté, ou null si le texte est propre.
     */
    public static function firstMatch(string $text): ?string
    {
        $haystack = mb_strtolower($text);

        foreach (self::KEYWORDS as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtolower($keyword))) {
                    return $topic;
                }
            }
        }

        return null;
    }
}
