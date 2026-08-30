import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanySectorBlock } from "./CompanySectorBlock";
import type { Company } from "@/types/company";

const baseCompany: Company = {
  slug: "acme",
  national_id: "123456789",
  legal_name: "Acme SAS",
  trade_name: null,
  legal_form_code: null,
  legal_form_label: null,
  status: "active",
  created_date: null,
  headcount_range: null,
  about_text: null,
  latitude: null,
  longitude: null,
  activity: null,
  city: null,
  district: null,
  main_establishment: null,
  nearby_pois: [],
};

describe("CompanySectorBlock", () => {
  it("renders nothing when the company has no activity", () => {
    const { container } = render(<CompanySectorBlock company={baseCompany} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders nothing when the activity has no sector", () => {
    const { container } = render(
      <CompanySectorBlock
        company={{
          ...baseCompany,
          activity: { slug: "transport", label: "Transport", sectors: [] },
        }}
      />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("renders the linked sectors", () => {
    render(
      <CompanySectorBlock
        company={{
          ...baseCompany,
          activity: {
            slug: "transport",
            label: "Transport",
            sectors: [{ slug: "transport-logistique", name: "Transport et logistique" }],
          },
        }}
      />,
    );

    expect(screen.getByText("Transport et logistique")).toBeInTheDocument();
  });
});
