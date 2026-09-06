/**
 * Emplacement publicitaire (CLAUDE.md §5, "Layout"). Dimensions réservées
 * en CSS pour ne jamais provoquer de décalage de mise en page (§5,
 * "leurs dimensions sont réservées en CSS pour éviter tout décalage") —
 * un espace vide tant que la Phase 07/monétisation ne branche pas un
 * véritable service de publicité.
 */
export function AdSlot({ label, className = "" }: { label: string; className?: string }) {
  return (
    <div
      className={`flex min-h-[250px] items-center justify-center rounded-[var(--radius-card)] border border-dashed border-[var(--border)] bg-[var(--surface)] px-4 text-center text-sm text-[var(--ink-muted)] ${className}`}
    >
      {label}
    </div>
  );
}
