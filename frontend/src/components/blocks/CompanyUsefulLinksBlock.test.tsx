import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyUsefulLinksBlock } from "./CompanyUsefulLinksBlock";
import type { Company, CompanyLinks } from "@/types/company";

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

  it("renders nothing when a link has no resolved path yet", () => {
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
              path: null,
            },
          },
        }}
      />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("renders same-trade and nearby companies using the path resolved by the backend", () => {
    render(
      <CompanyUsefulLinksBlock
        company={{
          ...baseCompany,
          links: {
            ...emptyLinks,
            sameTradeInCity: [
              {
                type: "company",
                label: "Keolis Marrakech",
                params: { slug: "keolis-marrakech" },
                path: "/marrakech/keolis-marrakech-01ARZ3NDEKTSV4RRFFQ69G5FAW",
              },
            ],
            nearby: [
              {
                type: "company",
                label: "Voisin SARL",
                params: { slug: "voisin-sarl" },
                path: "/marrakech/voisin-sarl-01ARZ3NDEKTSV4RRFFQ69G5FAX",
              },
            ],
          },
        }}
      />,
    );

    expect(screen.getByRole("link", { name: "Keolis Marrakech" })).toHaveAttribute(
      "href",
      "/marrakech/keolis-marrakech-01ARZ3NDEKTSV4RRFFQ69G5FAW",
    );
    expect(screen.getByRole("link", { name: "Voisin SARL" })).toHaveAttribute(
      "href",
      "/marrakech/voisin-sarl-01ARZ3NDEKTSV4RRFFQ69G5FAX",
    );
  });
});
