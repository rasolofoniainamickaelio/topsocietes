import { getCurrentCountry } from "@/lib/country/get-current-country";

/**
 * Page de vérification temporaire du mécanisme de résolution pays —
 * sera remplacée par la véritable page d'accueil dans une phase
 * ultérieure.
 */
export default async function HomePage() {
  const country = await getCurrentCountry();

  return (
    <main style={{ padding: "2rem", fontFamily: "sans-serif" }}>
      <h1>{country.name}</h1>
      <dl>
        <dt>Code</dt>
        <dd>{country.code}</dd>
        <dt>Sous-domaine</dt>
        <dd>{country.subdomain}</dd>
        <dt>Locale</dt>
        <dd>{country.default_locale}</dd>
        <dt>Devise</dt>
        <dd>{country.currency}</dd>
        <dt>Fuseau horaire</dt>
        <dd>{country.timezone}</dd>
      </dl>
    </main>
  );
}
