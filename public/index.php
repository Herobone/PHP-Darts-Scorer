<?php

$devEnv = getenv("DEV");
define('DEV', $devEnv != "");

include_once 'base.php';

$migrate = getenv("MIGRATE");
if ($migrate != "") {
    Database::migrate();
    echo "Migration abgeschlossen.";
    exit();
} else {
    $handler();
}