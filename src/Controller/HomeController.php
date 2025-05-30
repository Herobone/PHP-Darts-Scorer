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

    public function score() :void {
        $this->render('scoring');
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