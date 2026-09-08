import Link from "next/link";

/**
 * 404 (CLAUDE.md §6.5, §5 "écriture d'interface") : remplace la page
 * générique Next.js par défaut. Pas de layout de site (header/nav) pour
 * l'instant — la page d'accueil elle-même est encore un placeholder
 * (`(site)/page.tsx`) — donc ce 404 reste volontairement minimal plutôt
 * que d'inventer une navigation qui n'existe pas ailleurs sur le site.
 */
export default function NotFound() {
  return (
    <main className="mx-auto flex max-w-[720px] flex-col items-start gap-3 px-4 py-16">
      <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
        Page introuvable
      </h1>
      <p className="text-[1rem] leading-[1.6] text-[var(--ink-muted)]">
        Cette page n&apos;existe pas ou plus. Vérifiez l&apos;adresse, ou repartez de l&apos;accueil.
      </p>
      <Link
        href="/"
        className="mt-2 rounded-[var(--radius-chip)] bg-[var(--brand)] px-4 py-2 text-[var(--paper)]"
      >
        Retour à l&apos;accueil
      </Link>
    </main>
  );
}
