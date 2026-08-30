"use client";

import { useState, type FormEvent } from "react";
import { apiFetch, ApiError } from "@/lib/api/client";
import { SectionCard } from "@/components/ui/SectionCard";

type SubmitState = "idle" | "submitting" | "success" | "error";

/**
 * Formulaire avec état/soumission — justifie `"use client"` (CLAUDE.md
 * §4). Pas de retour de validation champ par champ pour cette première
 * passe : `apiFetch` ne remonte pas encore le corps d'une réponse 422
 * (limitation connue, acceptable vu la priorité "structure avant
 * contenu" de la Phase 05).
 */
export function CompanyDisputeBlock({
  country,
  slug,
}: {
  country: string;
  slug: string;
}) {
  const [state, setState] = useState<SubmitState>("idle");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setState("submitting");

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
      await apiFetch(country, `/companies/${slug}/disputes`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      setState("success");
      event.currentTarget.reset();
    } catch (error) {
      setState("error");
      if (!(error instanceof ApiError)) {
        throw error;
      }
    }
  }

  if (state === "success") {
    return (
      <SectionCard theme="stats" title="Contester une information">
        <p>
          Signalement reçu. Nous examinerons votre demande et vous
          contacterons si nécessaire.
        </p>
      </SectionCard>
    );
  }

  return (
    <SectionCard theme="stats" title="Contester une information">
      <form onSubmit={handleSubmit} className="flex flex-col gap-3 text-sm">
        <label className="flex flex-col gap-1">
          Champ concerné
          <input name="field" required maxLength={100} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Valeur actuelle (si connue)
          <input name="current_value" maxLength={1000} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Valeur correcte proposée
          <input name="proposed_value" required maxLength={1000} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Raison
          <textarea name="reason" required maxLength={2000} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Votre nom
          <input name="reporter_name" required maxLength={255} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Votre email
          <input name="reporter_email" type="email" required maxLength={255} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <label className="flex flex-col gap-1">
          Votre téléphone (facultatif)
          <input name="reporter_phone" maxLength={50} className="rounded-[var(--radius-chip)] border border-[var(--border)] px-2 py-1" />
        </label>
        <button
          type="submit"
          disabled={state === "submitting"}
          className="mt-2 self-start rounded-[var(--radius-chip)] bg-[var(--brand)] px-4 py-2 text-[var(--paper)] disabled:opacity-60"
        >
          {state === "submitting" ? "Envoi…" : "Envoyer le signalement"}
        </button>
        {state === "error" && (
          <p className="text-sm text-red-700">
            Une erreur est survenue. Vérifiez les champs et réessayez.
          </p>
        )}
      </form>
    </SectionCard>
  );
}
