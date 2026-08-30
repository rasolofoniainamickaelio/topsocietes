import { SectionCard } from "@/components/ui/SectionCard";
import type { Company } from "@/types/company";

const TYPE_LABELS: Record<string, string> = {
  phone: "Téléphone",
  mobile: "Mobile",
  email: "Email",
  website: "Site internet",
  fax: "Fax",
  social: "Réseau social",
};

/**
 * Ne reçoit jamais un contact masqué : le filtrage a lieu côté API
 * (CLAUDE.md §6.3, `ShowCompanyAction`) — ce composant se contente
 * d'afficher ce qu'on lui donne, ou de disparaître si rien n'est visible.
 */
export function CompanyContactBlock({ company }: { company: Company }) {
  const contacts = company.contacts ?? [];

  if (contacts.length === 0) {
    return null;
  }

  return (
    <SectionCard theme="contact" title="Contact">
      <dl className="flex flex-col gap-2 text-sm">
        {contacts.map((contact, index) => (
          <div key={`${contact.type}-${index}`} className="flex gap-2">
            <dt className="text-[var(--ink-muted)]">
              {TYPE_LABELS[contact.type] ?? contact.type}
            </dt>
            <dd>{contact.value}</dd>
          </div>
        ))}
      </dl>
    </SectionCard>
  );
}
