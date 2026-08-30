import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { FactPill } from "./FactPill";

describe("FactPill", () => {
  it("renders its content in a monospace pill", () => {
    render(<FactPill tone="company">123456789</FactPill>);

    const pill = screen.getByText("123456789");
    expect(pill).toBeInTheDocument();
    expect(pill.className).toContain("font-mono");
  });
});
