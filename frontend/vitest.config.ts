import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
import path from "node:path";

export default defineConfig({
  plugins: [react()],
  test: {
    environment: "jsdom",
    globals: true,
    // "forks" (le pool par défaut) échoue à démarrer ses workers sur cette
    // machine (probablement l'antivirus/EDR bloquant le spawn de process
    // enfants — même symptôme que l'échec d'installation MSI de Memurai).
    // "threads" évite le spawn de nouveaux processus OS.
    pool: "threads",
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
