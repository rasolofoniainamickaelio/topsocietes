import { afterEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { middleware } from "./middleware";

function makeRequest(host: string, path = "/companies/acme"): NextRequest {
  return new NextRequest(`http://${host}${path}`, { headers: { host } });
}

describe("middleware", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it("does not set X-Robots-Tag in production", () => {
    vi.stubEnv("NEXT_PUBLIC_BASE_DOMAIN", "topsocietes.test");
    vi.stubEnv("NEXT_PUBLIC_SITE_DEFAULT_COUNTRY", "fr");
    vi.stubEnv("APP_ENV", "production");

    const response = middleware(makeRequest("fr.topsocietes.test"));

    expect(response.headers.get("X-Robots-Tag")).toBeNull();
  });

  it("sets X-Robots-Tag: noindex outside production", () => {
    vi.stubEnv("NEXT_PUBLIC_BASE_DOMAIN", "topsocietes.test");
    vi.stubEnv("NEXT_PUBLIC_SITE_DEFAULT_COUNTRY", "fr");
    vi.stubEnv("APP_ENV", "preprod");

    const response = middleware(makeRequest("fr.topsocietes.test"));

    expect(response.headers.get("X-Robots-Tag")).toBe("noindex, nofollow");
  });

  it("treats a missing APP_ENV as production, same default as config('app.env') backend", () => {
    vi.stubEnv("NEXT_PUBLIC_BASE_DOMAIN", "topsocietes.test");
    vi.stubEnv("NEXT_PUBLIC_SITE_DEFAULT_COUNTRY", "fr");
    delete process.env.APP_ENV;

    const response = middleware(makeRequest("fr.topsocietes.test"));

    expect(response.headers.get("X-Robots-Tag")).toBeNull();
  });

  it("still sets X-Robots-Tag on the apex-domain redirect", () => {
    vi.stubEnv("NEXT_PUBLIC_BASE_DOMAIN", "topsocietes.test");
    vi.stubEnv("NEXT_PUBLIC_SITE_DEFAULT_COUNTRY", "fr");
    vi.stubEnv("APP_ENV", "preprod");

    const response = middleware(makeRequest("topsocietes.test"));

    expect(response.status).toBe(307);
    expect(response.headers.get("X-Robots-Tag")).toBe("noindex, nofollow");
  });
});
