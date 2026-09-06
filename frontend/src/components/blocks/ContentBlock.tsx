import { SectionCard } from "@/components/ui/SectionCard";
import { themeForBlockType } from "@/lib/blocks/registry";
import type { ContentBlock as ContentBlockData } from "@/types/company";

/**
 * Rend un bloc de contenu territorial/sectoriel mutualisé (Phase 08) via
 * le registre de blocs (CLAUDE.md §4). Un `type` non reconnu, ou un bloc
 * sans titre/corps exploitable, est ignoré silencieusement plutôt que de
 * produire une erreur de rendu ou un bloc vide (§6.5).
 */
export function ContentBlock({ block }: { block: ContentBlockData }) {
  const theme = themeForBlockType(block.type);

  if (theme === null || !block.data.title || !block.data.body) {
    return null;
  }

  return (
    <SectionCard theme={theme} title={block.data.title}>
      <p className="text-[1rem] leading-[1.6] whitespace-pre-line">{block.data.body}</p>
    </SectionCard>
  );
}
