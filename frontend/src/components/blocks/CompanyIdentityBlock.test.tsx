import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CompanyIdentityBlock } from "./CompanyIdentityBlock";
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

describe("CompanyIdentityBlock", () => {
  it("shows the legal name and a translated status, but not a trade name when absent", () => {
    render(<CompanyIdentityBlock company={baseCompany} />);

    expect(screen.getByText("Acme SAS")).toBeInTheDocument();
    expect(screen.getByText("Active")).toBeInTheDocument();
  });

  it("shows the trade name when present", () => {
    render(
      <CompanyIdentityBlock company={{ ...baseCompany, trade_name: "Acme" }} />,
    );

    expect(screen.getByText("Acme")).toBeInTheDocument();
  });

  it("falls back to the raw value for an unrecognized status", () => {
    render(<CompanyIdentityBlock company={{ ...baseCompany, status: "suspended" }} />);

    expect(screen.getByText("suspended")).toBeInTheDocument();
  });
});
