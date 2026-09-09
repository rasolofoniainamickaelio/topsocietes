import { SectionCard } from "@/components/ui/SectionCard";
import { themeForBlockType } from "@/lib/blocks/registry";
import type { ContentBlock } from "@/types/company";

/**
 * Cette page mêle du contenu croisé (`city_activity_contents`, sans colonne
 * `title` — composé ici, jamais stocké en base, CLAUDE.md §5 "écriture
 * d'interface") et du contenu générique du métier (`activity_contents`, qui
 * a bien un `title` propre — toujours préféré quand présent). Miroir de
 * `ContentBlock.tsx` sinon (même registre de thèmes, même règle "pas de
 * corps = bloc absent", CLAUDE.md §6.5).
 */
const SECTION_TITLES: Record<string, (activityLabel: string, cityName: string) => string> = {
  local_overview: (activityLabel, cityName) => `${activityLabel} à ${cityName}`,
  local_history: (activityLabel, cityName) => `Histoire de ce métier à ${cityName}`,
  local_specifics: () => "Spécificités locales",
  local_economy: (activityLabel, cityName) => `Poids économique à ${cityName}`,
  faq: () => "Questions fréquentes",
};

export function CityActivityContentBlock({
  block,
  activityLabel,
  cityName,
}: {
  block: ContentBlock;
  activityLabel: string;
  cityName: string;
}) {
  const theme = themeForBlockType(block.type);

  if (theme === null || !block.data.body) {
    return null;
  }

  const title = block.data.title ?? SECTION_TITLES[block.type]?.(activityLabel, cityName) ?? activityLabel;

  return (
    <SectionCard theme={theme} title={title}>
      <p className="text-[1rem] leading-[1.6] whitespace-pre-line">{block.data.body}</p>
    </SectionCard>
  );
}
