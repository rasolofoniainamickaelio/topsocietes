import type { Metadata } from "next";
import { headers } from "next/headers";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import { ChildDivisionsList } from "@/components/admin-division/ChildDivisionsList";
import { ActivityLinksSection } from "@/components/activity/ActivityLinksSection";

/**
 * Page pays réelle (Phase 13) : dernier des 6 types de page territoriale,
 * cas particulier — c'est la racine du sous-domaine (`/`), donc hors du
 * catch-all générique `[...path]` (qui ne reçoit jamais de segment vide).
 * Aucun contenu de prose propre (pas de `CountryContent` : périmètre non
 * retenu) — une page de liens vers les régions et les activités
 * principales déjà synchronisées, jamais un lien mort (CLAUDE.md §6.5).
 */
export async function generateMetadata(): Promise<Metadata> {
  const country = await getCurrentCountry();
  const host = (await headers()).get("host");
  const robots = country.is_indexable === false ? { index: false, follow: false } : undefined;

  return {
    title: country.name,
    alternates: host ? { canonical: `https://${host}/` } : undefined,
    robots,
  };
}

export default async function HomePage() {
  const country = await getCurrentCountry();

  return (
    <div className="mx-auto flex max-w-[720px] flex-col gap-[var(--space-block)] px-4 py-8">
      <header className="border-b border-[var(--border)] pb-6">
        <h1 className="font-display text-[1.6875rem] leading-[1.15] font-bold text-[var(--ink)] md:text-[2rem]">
          {country.name}
        </h1>
      </header>

      <ChildDivisionsList divisions={country.links?.regions ?? []} />
      <ActivityLinksSection title="Activités principales" theme="sector" links={country.links?.activities ?? []} />
    </div>
  );
}
