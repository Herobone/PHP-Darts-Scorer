<?php

use App\Core\Database;

$devEnv = getenv("DEV");
define('DEV', $devEnv != "");

/** @var callable $handler */
include_once 'base.php';

$migrate = getenv("MIGRATE");
if ($migrate != "") {
    Database::migrate();
    echo "Migration abgeschlossen.";
    exit();
} else {
    $handler();
}