import { defineConfig } from 'vite';
import dns from 'dns';
import tailwindcss from "@tailwindcss/vite";
import * as path from "node:path"; // Um localhost statt 127.0.0.1 für den Dev-Server zu verwenden

// Damit der Vite Dev Server auf localhost hört und nicht auf 127.0.0.1
// Das kann manchmal bei PHP-Setups und Docker nützlich sein.
dns.setDefaultResultOrder('verbatim');

export default defineConfig({
    // Wurzelverzeichnis deines Projekts (wo die vite.config.ts liegt)
    root: '.',
    // Öffentlicher Basispfad, von dem Assets im Browser geladen werden.
    // Wichtig, wenn dein PHP-Projekt nicht im Web-Root läuft.
    // Für den Dev-Server ist dies der Pfad, unter dem Vite die Assets bereitstellt.
    base: '/', // Oder einfach '/', wenn dein Projekt im Root läuft und Vite für den Dev-Mode auch dort serviert

    // Konfiguration für den Build-Prozess
    build: {
        // Ausgabeverzeichnis für den Build (relativ zum 'root')
        outDir: './build',
        // Leere das outDir vor jedem Build
        emptyOutDir: true,
        sourcemap: true,
        // Erzeuge ein Manifest-File, das PHP nutzen kann, um Asset-Pfade aufzulösen
        manifest: true,
        rollupOptions: {
            // Definiere deine Einstiegspunkte
            input: {
                main: './assets/ts/main.ts',
            },
            output: {
                // Stelle sicher, dass die Dateinamen keine Hashes im Dev-Modus bekommen,
                // aber für Produktion schon (für Cache-Busting). Vite macht das meist richtig.
                entryFileNames: `[name].js`,
                chunkFileNames: `[name].js`,
                assetFileNames: `[name].[ext]`,
            }
        },
    },

    // Konfiguration für den Development Server
    server: {
        // Host, auf dem der Vite Dev Server laufen soll
        host: 'localhost', // Oder '0.0.0.0' um von anderen Geräten im Netzwerk erreichbar zu sein
        port: 5173, // Standard Vite Port, kann geändert werden
        strictPort: true, // Fehler werfen, wenn der Port belegt ist
        // Wichtig für die Integration mit deinem PHP Backend-Server:
        // Wir sagen Vite, wo es die Assets während der Entwicklung bereitstellen soll.
        // Dein PHP muss diese URLs dann verwenden.
        origin: 'http://localhost:5173', // Die URL des Vite Dev Servers

        // Optional: Proxy-Anfragen an deinen PHP-Server, wenn du alles über den Vite-Port laufen lassen willst
        // proxy: {
        //   '/api': 'http://localhost:8000', // Leitet Anfragen an /api an deinen PHP-Server weiter
        //   // Du kannst hier auch PHP-Dateien proxyen, aber das ist oft komplexer als nötig.
        // }
    },

    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./assets/ts"),
            "@css": path.resolve(__dirname, "./assets/css")
        }
    },

    plugins: [
        tailwindcss()
    ]
});