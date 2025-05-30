<?php

use App\Core\Database;

ignore_user_abort(true);

// Definiere eine Konstante für den Basispfad des Projekts
define('BASE_PATH', dirname(__DIR__));

$devEnv = getenv("DEV");
define('DEV', $devEnv != "");

/** @var callable $handler */
include_once BASE_PATH . '/src/base.php';

$migrate = getenv("MIGRATE");
if ($migrate != "") {
    Database::migrate();
    echo "Migration abgeschlossen.";
    exit();
} else {
    $handler();
}