import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyActivityBlock } from "./CompanyActivityBlock";
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

describe("CompanyActivityBlock", () => {
  it("renders nothing when the company has no resolved activity", () => {
    const { container } = render(<CompanyActivityBlock company={baseCompany} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders the activity label", () => {
    render(
      <CompanyActivityBlock
        company={{
          ...baseCompany,
          activity: { slug: "transport-urbain", label: "Transport urbain", sectors: [] },
        }}
      />,
    );

    expect(screen.getByText("Transport urbain")).toBeInTheDocument();
  });
});
