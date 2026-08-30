import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyContactBlock } from "./CompanyContactBlock";
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

describe("CompanyContactBlock", () => {
  it("renders nothing when there are no visible contacts", () => {
    const { container } = render(<CompanyContactBlock company={baseCompany} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders visible contacts", () => {
    render(
      <CompanyContactBlock
        company={{
          ...baseCompany,
          contacts: [{ type: "phone", value: "+33100000000" }],
        }}
      />,
    );

    expect(screen.getByText("+33100000000")).toBeInTheDocument();
    expect(screen.getByText("Téléphone")).toBeInTheDocument();
  });
});
