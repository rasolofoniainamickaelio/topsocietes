import type { ReactNode } from "react";

export type FactTone =
  | "company"
  | "contact"
  | "nearby"
  | "district"
  | "history"
  | "nature"
  | "leisure"
  | "specialty"
  | "stats"
  | "sector";

/**
 * Élément signature du "fil des faits" (CLAUDE.md §5) : tout atome
 * factuel — identifiant, date, distance, code — composé en monospace sur
 * une pastille teintée, pour se distinguer d'un coup d'œil de la prose
 * rédigée.
 */
export function FactPill({
  children,
  tone = "company",
}: {
  children: ReactNode;
  tone?: FactTone;
}) {
  const toneVar = `var(--t-${tone})`;

  return (
    <span
      className="inline-flex items-center rounded-[var(--radius-chip)] px-2 py-0.5 font-mono text-[0.875rem] leading-[1.4]"
      style={{
        backgroundColor: `color-mix(in srgb, ${toneVar} 12%, var(--paper))`,
        color: toneVar,
      }}
    >
      {children}
    </span>
  );
}
