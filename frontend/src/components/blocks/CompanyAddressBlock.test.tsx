import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyAddressBlock } from "./CompanyAddressBlock";
import type { Company } from "@/types/company";

const baseCompany: Company = {
  slug: "acme",
  public_id: "01ARZ3NDEKTSV4RRFFQ69G5FAV",
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

describe("CompanyAddressBlock", () => {
  it("renders nothing when the company has no main establishment", () => {
    const { container } = render(<CompanyAddressBlock company={baseCompany} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders the street line, complement and postal code with city", () => {
    render(
      <CompanyAddressBlock
        company={{
          ...baseCompany,
          city: { slug: "marrakech", name: "Marrakech" },
          main_establishment: {
            street_number: "12",
            street_name: "Avenue Mohammed V",
            address_line2: "Résidence Al Andalous, 3e étage",
            postal_code: "40000",
            is_headquarters: true,
          },
        }}
      />,
    );

    expect(screen.getByText("12 Avenue Mohammed V")).toBeInTheDocument();
    expect(screen.getByText("Résidence Al Andalous, 3e étage")).toBeInTheDocument();
    expect(screen.getByText("40000 Marrakech")).toBeInTheDocument();
  });

  it("omits the complement line and tolerates a missing postal code", () => {
    render(
      <CompanyAddressBlock
        company={{
          ...baseCompany,
          city: { slug: "marrakech", name: "Marrakech" },
          main_establishment: {
            street_number: null,
            street_name: "Avenue Mohammed V",
            address_line2: null,
            postal_code: null,
            is_headquarters: true,
          },
        }}
      />,
    );

    expect(screen.getByText("Avenue Mohammed V")).toBeInTheDocument();
    expect(screen.getByText("Marrakech")).toBeInTheDocument();
  });
});
