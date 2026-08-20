/**
 * Extrait le sous-domaine pays d'un en-tête Host. Purement générique — ne
 * connaît aucun code pays (CLAUDE.md §6.6). Retourne `null` pour le
 * domaine racine, `www`, ou tout hôte qui ne se termine pas par
 * `baseDomain` (ex. `localhost` en dev sans sous-domaine).
 */
export function resolveSubdomain(
  host: string | null | undefined,
  baseDomain: string,
): string | null {
  if (!host) {
    return null;
  }

  const hostWithoutPort = host.split(":")[0].toLowerCase();
  const normalizedBase = baseDomain.toLowerCase();

  if (
    hostWithoutPort === normalizedBase ||
    hostWithoutPort === `www.${normalizedBase}`
  ) {
    return null;
  }

  const suffix = `.${normalizedBase}`;

  if (!hostWithoutPort.endsWith(suffix)) {
    return null;
  }

  const label = hostWithoutPort.slice(0, -suffix.length);

  return label.length > 0 ? label : null;
}
