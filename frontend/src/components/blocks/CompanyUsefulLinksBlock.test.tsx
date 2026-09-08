import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyUsefulLinksBlock } from "./CompanyUsefulLinksBlock";
import type { Company, CompanyLinks } from "@/types/company";

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

const emptyLinks: CompanyLinks = {
  sameTradeInCity: [],
  nearby: [],
  activityInCity: null,
  activityInNeighborCities: [],
  department: null,
  region: null,
  country: null,
  relatedActivities: [],
  companyCreation: null,
};

describe("CompanyUsefulLinksBlock", () => {
  it("renders nothing when the company has no links", () => {
    const { container } = render(<CompanyUsefulLinksBlock company={baseCompany} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders nothing when every group is empty", () => {
    const { container } = render(
      <CompanyUsefulLinksBlock company={{ ...baseCompany, links: emptyLinks }} />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("renders nothing when only non-routable link types are present", () => {
    const { container } = render(
      <CompanyUsefulLinksBlock
        company={{
          ...baseCompany,
          links: {
            ...emptyLinks,
            activityInCity: {
              type: "activity_city",
              label: "Transport urbain à Marrakech",
              params: { citySlug: "marrakech", activitySlug: "transport-urbain" },
            },
          },
        }}
      />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("renders same-trade and nearby companies as links to their fiche", () => {
    render(
      <CompanyUsefulLinksBlock
        company={{
          ...baseCompany,
          links: {
            ...emptyLinks,
            sameTradeInCity: [
              { type: "company", label: "Keolis Marrakech", params: { slug: "keolis-marrakech" } },
            ],
            nearby: [
              { type: "company", label: "Voisin SARL", params: { slug: "voisin-sarl" } },
            ],
          },
        }}
      />,
    );

    expect(screen.getByRole("link", { name: "Keolis Marrakech" })).toHaveAttribute(
      "href",
      "/companies/keolis-marrakech",
    );
    expect(screen.getByRole("link", { name: "Voisin SARL" })).toHaveAttribute(
      "href",
      "/companies/voisin-sarl",
    );
  });
});
