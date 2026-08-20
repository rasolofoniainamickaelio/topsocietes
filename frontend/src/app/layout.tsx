import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import { getCurrentCountry } from "@/lib/country/get-current-country";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
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
        className={`${geistSans.variable} ${geistMono.variable} antialiased`}
      >
        {children}
      </body>
    </html>
  );
}
