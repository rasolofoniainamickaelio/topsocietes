/** Erreur levée par `apiFetch` pour toute réponse HTTP non 2xx. */
export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

/**
 * Client fetch typé minimal vers l'API Laravel. Le pays est toujours
 * passé explicitement (jamais déduit ici) — voir docs/DATABASE.md :
 * l'API n'est pas routée par sous-domaine, c'est l'appelant (Server
 * Component) qui a déjà résolu le pays depuis son propre sous-domaine.
 */
export async function apiFetch<T>(
  country: string,
  path: string,
  init?: RequestInit,
): Promise<T> {
  const baseUrl = process.env.API_BASE_URL;

  if (!baseUrl) {
    throw new Error("API_BASE_URL is not configured");
  }

  const url = `${baseUrl}/${country}${path}`;
  const response = await fetch(url, init);

  if (!response.ok) {
    throw new ApiError(`API request to ${url} failed`, response.status);
  }

  return (await response.json()) as T;
}
