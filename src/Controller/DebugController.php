<?php

namespace App\Controller;

use App\Core\Database;
use ByJG\DbMigration\Exception\DatabaseDoesNotRegistered;
use ByJG\DbMigration\Exception\DatabaseIsIncompleteException;
use ByJG\DbMigration\Exception\DatabaseNotVersionedException;
use ByJG\DbMigration\Exception\InvalidMigrationFile;
use ByJG\DbMigration\Exception\OldVersionSchemaException;
use Exception;

class DebugController
{

    public function index(): void
    {
        // Zeigt die Debug-Seite an
        echo "<h1>Debugging-Informationen</h1>";
        echo '<p><a href="/">Zurück zur Startseite</a></p>';
    }

    public function phpInfo(): void
    {
        // Zeigt die PHP-Info-Seite an
        phpinfo();
    }

    public function migrate(): void {
        try {
            Database::migrate();
        } catch (Exception $e) {
            // Fehlerbehandlung
            echo "Migration fehlgeschlagen: " . $e->getMessage();
            return;
        }
        echo "Migration abgeschlossen.";
    }

}