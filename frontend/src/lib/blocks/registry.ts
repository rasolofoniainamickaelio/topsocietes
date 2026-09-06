import type { FactTone } from "@/components/ui/FactPill";

/**
 * Registre de blocs (CLAUDE.md §4) : associe chaque `type` renvoyé par
 * l'API (clés de `App\Domain\Content\Enums\ContentSection`, backend) à la
 * teinte thématique du système de design (§5) qui l'affiche. Un `type`
 * absent de ce registre est ignoré silencieusement par l'appelant — ne
 * jamais faire planter le rendu pour une section future non encore
 * cablée ici.
 */
const BLOCK_THEMES: Record<string, FactTone> = {
  history: "history",
  nature: "nature",
  leisure: "leisure",
  specialty: "specialty",
  stats: "stats",
  faq: "sector",
  understanding_sector: "sector",
  how_it_works: "sector",
  jobs: "sector",
  diplomas: "sector",
  regulation: "sector",
  common_mistakes: "sector",
  how_to_choose: "sector",
  business_creation: "sector",
  local_overview: "district",
  local_history: "district",
  local_specifics: "district",
  local_economy: "district",
};

export function themeForBlockType(type: string): FactTone | null {
  return BLOCK_THEMES[type] ?? null;
}
