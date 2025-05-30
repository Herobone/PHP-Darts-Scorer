<?php
namespace App\Core;

use Exception;

class ViteAssets {
    private static string $manifestPath = BASE_PATH . '/build/.vite/manifest.json';
    private static string $viteDevServer = 'http://localhost:5173'; // Muss mit vite.config.ts server.origin übereinstimmen
    private static bool $isDev;

    private static ?array $manifestData = null;

    public static function init(bool $isDevelopmentMode): void {
        self::$isDev = $isDevelopmentMode;
    }

    /**
     * @throws Exception
     */
    private static function getManifest(): ?array {
        if (self::$manifestData !== null) {
            return self::$manifestData;
        }
        if (!file_exists(self::$manifestPath)) {
            // Im Dev-Modus ist das okay, da wir nicht auf das Manifest angewiesen sind
            if (self::$isDev) return null;
            throw new Exception("Vite manifest nicht gefunden unter: " . self::$manifestPath . ". Führe 'bun run build' aus.");
        }
        self::$manifestData = json_decode(file_get_contents(self::$manifestPath), true);
        return self::$manifestData;
    }

    public static function asset(string $entrypointKey): string {
        if (self::$isDev) {
            // Im Dev-Modus laden wir direkt vom Vite Dev Server
            // Der $entrypointKey ist der Schlüssel aus vite.config.js build.rollupOptions.input
            // z.B. 'assets/ts/main.ts' wenn dein input so definiert ist: input: { main: './assets/ts/main.ts'}
            // Vite erwartet den Pfad relativ zum Projekt-Root.
            // In der Regel ist es einfacher, den Key zu verwenden, den du in rollupOptions.input definiert hast, z.B. 'main'
            // oder direkt den Pfad relativ zum vite root
            // Wir brauchen hier den Pfad zum *ursprünglichen* Source File, Vite kümmert sich um den Rest.
            return self::$viteDevServer . '/assets/ts/' . $entrypointKey; // Beispiel: http://localhost:5173/assets/ts/main.ts
            // oder wenn dein input key 'main' war und auf './assets/ts/main.ts' zeigt:
            // return self::$viteDevServer . '/assets/ts/main.ts'; (Der Pfad muss der Source-Datei entsprechen, wie Vite sie kennt)
            // Wenn dein input: `main: 'assets/ts/main.ts'` war, dann eher so:
            // return self::$viteDevServer . '/' . $entrypointKey;
            // Vite erwartet den Pfad relativ zum root der vite.config.js
            // Für `input: { main: './assets/ts/main.ts' }` ist der Request an Vite: `http://localhost:5173/assets/ts/main.ts`
        } else {
            // Im Produktions-Modus verwenden wir das Manifest
            $manifest = self::getManifest();
            $originalEntryPath = 'assets/ts/' . $entrypointKey; // z.B. assets/ts/main.ts

            if ($manifest && isset($manifest[$originalEntryPath]['file'])) {
                return '/' . $manifest[$originalEntryPath]['file']; // z.B. /build/main.agsf72.js
            } else {
                // Fallback oder Fehler, wenn der Eintrag nicht im Manifest ist
                // Dies sollte nicht passieren, wenn der Build korrekt war.
                error_log("Vite asset '{$originalEntryPath}' nicht im Manifest gefunden.");
                return '/' . $entrypointKey; // Versuch eines Fallbacks, könnte fehlschlagen
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function cssTags(string $entrypointKey): array
    {
        if (self::$isDev) {
            return array();
        } else {
            $manifest = self::getManifest();
            $originalEntryPath = 'assets/ts/' . $entrypointKey;
            $cssTags = array();
            if ($manifest && isset($manifest[$originalEntryPath]['css'])) {
                foreach ($manifest[$originalEntryPath]['css'] as $cssFile) {
                    $cssTags[] = $cssFile;
                }
            }
            return $cssTags;
        }
    }

    // Im Dev-Modus müssen wir auch den Vite Client für HMR einbinden
    public static function HMRClient(): string {
        if (self::$isDev) {
            return self::$viteDevServer . '/@vite/client';
        }
        return '';
    }
}