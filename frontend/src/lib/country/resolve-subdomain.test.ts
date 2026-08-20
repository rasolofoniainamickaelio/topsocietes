import { describe, expect, it } from "vitest";
import { resolveSubdomain } from "./resolve-subdomain";

const BASE_DOMAIN = "topsocietes.test";

describe("resolveSubdomain", () => {
  it("returns null for the apex domain", () => {
    expect(resolveSubdomain("topsocietes.test", BASE_DOMAIN)).toBeNull();
  });

  it("returns null for www", () => {
    expect(resolveSubdomain("www.topsocietes.test", BASE_DOMAIN)).toBeNull();
  });

  it("extracts a valid country subdomain", () => {
    expect(resolveSubdomain("fr.topsocietes.test", BASE_DOMAIN)).toBe("fr");
  });

  it("strips the port before extracting the subdomain", () => {
    expect(resolveSubdomain("fr.topsocietes.test:3000", BASE_DOMAIN)).toBe(
      "fr",
    );
  });

  it("is case-insensitive", () => {
    expect(resolveSubdomain("FR.TOPSOCIETES.TEST", BASE_DOMAIN)).toBe("fr");
  });

  it("returns null for a host that does not match the base domain", () => {
    expect(resolveSubdomain("localhost:3000", BASE_DOMAIN)).toBeNull();
  });

  it("returns null for a missing host", () => {
    expect(resolveSubdomain(null, BASE_DOMAIN)).toBeNull();
  });
});
