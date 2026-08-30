import type { ReactNode } from "react";
import type { FactTone } from "@/components/ui/FactPill";

/**
 * Enveloppe d'un bloc thématique (CLAUDE.md §5) : fond teinté à 6 %,
 * bordure à 24 %, filet gauche 3px pleine saturation, titre dans la
 * couleur du thème. Ne décide jamais elle-même si le bloc doit s'afficher
 * — c'est au composant appelant de ne pas la rendre quand la donnée
 * manque (CLAUDE.md §6.5).
 */
export function SectionCard({
  theme,
  title,
  icon,
  children,
}: {
  theme: FactTone;
  title: string;
  icon?: ReactNode;
  children: ReactNode;
}) {
  const toneVar = `var(--t-${theme})`;

  return (
    <section
      className="rounded-[var(--radius-card)] border p-6"
      style={{
        backgroundColor: `color-mix(in srgb, ${toneVar} 6%, var(--paper))`,
        borderColor: `color-mix(in srgb, ${toneVar} 24%, transparent)`,
        borderLeftWidth: "3px",
        borderLeftColor: toneVar,
      }}
    >
      <h2
        className="mb-4 flex items-center gap-2 font-display text-[1.1875rem] leading-[1.3] font-semibold"
        style={{ color: toneVar }}
      >
        {icon}
        {title}
      </h2>
      {children}
    </section>
  );
}
