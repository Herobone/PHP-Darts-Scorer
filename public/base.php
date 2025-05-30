<?php

use App\Controller\HomeController;
use App\Core\BaseController;
use App\Core\ViteAssets;
use App\Core\PostgresSessionHandler;

ignore_user_abort(true);

// Definiere eine Konstante für den Basispfad des Projekts
define('BASE_PATH', dirname(__DIR__)); // Zeigt auf das 'dart-scorer' Verzeichnis

// Lade den Composer Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

require_once "config.php";

$handler = new PostgresSessionHandler();
session_set_save_handler($handler, true);
session_start();

ViteAssets::init(DEV);
BaseController::init();

// Initialisiere unseren Router
$router = new App\Core\Router();

// Definiere Routen
// Format: $router->addRoute('HTTP_METHOD', '/url_pfad', ControllerName::class, 'methodenName');

// Beispiel-Routen:
$router->addRoute('GET', '/', App\Controller\HomeController::class, 'index');
$router->addRoute('GET', '/about', App\Controller\HomeController::class, 'about');
$router->addRoute('GET', '/koks', App\Controller\HomeController::class, 'koks');
$router->addRoute('GET', '/phpinfo', App\Controller\HomeController::class, 'php_info');
$router->addRoute('GET', '/login', App\Controller\AuthController::class, 'login');
$router->addRoute('POST', '/login', App\Controller\AuthController::class, 'handleLogin');
$router->addRoute('GET', '/register', App\Controller\AuthController::class, 'register');
$router->addRoute('POST', '/register', App\Controller\AuthController::class, 'handleRegister');
$router->addRoute('GET', '/logout', App\Controller\AuthController::class, 'logout');
$router->addRoute("GET", "/score", HomeController::class, 'score');

$handler = static function () use ($router) {
    // Hole die aktuelle angeforderte URI und Methode
    $uri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    // Führe die passende Route aus
    try {
        $router->dispatch($requestMethod, $uri);
    } catch (Exception $e) {
        http_response_code($e->getCode());
        echo "<h1>Seite nicht gefunden (" . $e->getCode() . ")</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        // Optional: Logge den Fehler
        error_log($e->getMessage());
    }
};

