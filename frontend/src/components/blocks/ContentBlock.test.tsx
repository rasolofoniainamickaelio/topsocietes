import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { ContentBlock } from "./ContentBlock";
import type { ContentBlock as ContentBlockData } from "@/types/company";

describe("ContentBlock", () => {
  it("renders a known block type with its title and body", () => {
    const block: ContentBlockData = {
      type: "history",
      data: { title: "La Médina de Marrakech", body: "Fondée au XIe siècle." },
    };

    render(<ContentBlock block={block} />);

    expect(screen.getByText("La Médina de Marrakech")).toBeInTheDocument();
    expect(screen.getByText("Fondée au XIe siècle.")).toBeInTheDocument();
  });

  it("silently ignores an unrecognized block type", () => {
    const block: ContentBlockData = {
      type: "some_future_section",
      data: { title: "Titre", body: "Corps" },
    };

    const { container } = render(<ContentBlock block={block} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("silently ignores a known type with no body", () => {
    const block: ContentBlockData = {
      type: "history",
      data: { title: "Titre", body: null },
    };

    const { container } = render(<ContentBlock block={block} />);

    expect(container).toBeEmptyDOMElement();
  });
});
