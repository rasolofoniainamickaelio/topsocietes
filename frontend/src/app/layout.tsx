import type { Metadata } from "next";
import { Archivo, IBM_Plex_Mono, Public_Sans } from "next/font/google";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import "./globals.css";

// Display : titres de blocs, très visibles (CLAUDE.md §5).
const archivo = Archivo({
  variable: "--font-archivo",
  subsets: ["latin"],
  weight: ["600", "700"],
});

// Texte courant : prose, listes, FAQ (CLAUDE.md §5).
const publicSans = Public_Sans({
  variable: "--font-public-sans",
  subsets: ["latin"],
  weight: ["400", "500", "600"],
});

// Données : identifiants, codes, dates, distances — le "fil des faits"
// (CLAUDE.md §5).
const ibmPlexMono = IBM_Plex_Mono({
  variable: "--font-ibm-plex-mono",
  subsets: ["latin"],
  weight: ["500"],
});

export const metadata: Metadata = {
  title: "TOPsocietes.com",
  description: "Annuaire d'entreprises",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  // Résolution pays côté serveur : `<html lang>` ne peut être posé que
  // depuis le layout racine (seul niveau autorisé à rendre <html>/<body>
  // dans l'App Router), d'où la résolution ici plutôt que dans un layout
  // de groupe de routes imbriqué.
  const country = await getCurrentCountry();

  return (
    <html lang={country.default_locale.replace("_", "-")}>
      <body
        className={`${archivo.variable} ${publicSans.variable} ${ibmPlexMono.variable} antialiased`}
      >
        {children}
      </body>
    </html>
  );
}
