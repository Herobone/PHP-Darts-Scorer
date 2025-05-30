<?php

namespace App\Controller; 

use App\Core\BaseController;// Namespace für Controller

class HomeController extends BaseController {

    /**
     * Zeigt die Startseite an.
     */
    public function index(): void {
        $this->render('index');
    }

    public function php_info(): void {
        // Zeigt die PHP-Info-Seite an
        phpinfo();
        xdebug_info();
    }

    public function score() :void {
        $this->render('scoring');
    }

    public function koks() : void {
        $this->render('home', [
            'pageTitle' => "Koks?",
            'message' => "Naja ist meistens nicht so geil. Nicht zu empfehlen"
        ]);
    }

    /**
     * Zeigt eine "Über uns"-Seite an (Beispiel).
     */
    public function about(): void {
        $pageTitle = "Über uns";
        $content = "Hier könnten Informationen über das Projekt stehen.";

        // Du könntest hier eine andere View-Datei laden oder die gleiche mit anderen Daten
        // Für dieses Beispiel rendern wir einfach direkt HTML (nicht die beste Praxis für größere Seiten)
        echo "<h1>" . htmlspecialchars($pageTitle) . "</h1>";
        echo "<p>" . htmlspecialchars($content) . "</p>";
        echo '<p><a href="/">Zurück zur Startseite</a></p>';
    }
}