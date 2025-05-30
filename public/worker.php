<?php

use App\Core\Database;
use App\Core\ViteAssets;

const DEV = false;
ignore_user_abort(true);

// Definiere eine Konstante für den Basispfad des Projekts
define('BASE_PATH', dirname(__DIR__));

/** @var callable $handler */
include_once BASE_PATH . '/src/base.php';

ViteAssets::init(DEV);

try {
    Database::migrate();
} catch (Exception $e) {
    echo "Migration fehlgeschlagen: " . $e->getMessage();
    exit(1);
}
echo "Migration abgeschlossen.";

$maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
echo "Max Requests: $maxRequests\n";
for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
    $keepRunning = \frankenphp_handle_request($handler);

    // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
    gc_collect_cycles();

    if (!$keepRunning) break;
}

Database::closeConnection();