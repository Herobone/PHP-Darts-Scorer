<?php

use App\Controller\DebugController;
use App\Controller\HomeController;
use App\Core\BaseController;
use App\Core\ViteAssets;
use App\Core\PostgresSessionHandler;

// Lade den Composer Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

require_once BASE_PATH . "/config.php";

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
$router->addRoute('GET', '/login', App\Controller\AuthController::class, 'login');
$router->addRoute('POST', '/login', App\Controller\AuthController::class, 'handleLogin');
$router->addRoute('GET', '/register', App\Controller\AuthController::class, 'register');
$router->addRoute('POST', '/register', App\Controller\AuthController::class, 'handleRegister');
$router->addRoute('GET', '/logout', App\Controller\AuthController::class, 'logout');
$router->addRoute("GET", "/score", HomeController::class, 'score');

// Game routes
$router->addRoute('GET', '/game/create', App\Controller\GameController::class, 'create');
$router->addRoute('POST', '/game/create', App\Controller\GameController::class, 'store');
$router->addRoute('GET', '/game/score', App\Controller\ScoreController::class, 'score');
$router->addRoute('POST', '/game/score', App\Controller\ScoreController::class, 'submit');
$router->addRoute('POST', '/game/score/undo', App\Controller\ScoreController::class, 'undo');

// History routes
$router->addRoute('GET', '/history', App\Controller\HistoryController::class, 'index');
$router->addRoute('POST', '/history/delete', App\Controller\HistoryController::class, 'delete');

if (DEV) {
    $router->addRoute("GET", "/debug/migrate", DebugController::class, 'migrate');
    $router->addRoute('GET', '/debug/phpinfo', DebugController::class, 'phpInfo');
}

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
        if ($e->getCode() === 0) {
            // Setze einen generischen Fehlercode, wenn keiner angegeben ist
            $e = new Exception($e->getMessage(), 500);
        }
        http_response_code($e->getCode());

        // Zeige eine generische Fehlerseite an
        echo "<h1>Error " . $e->getCode() . "</h1>";
        echo "<p>" . $e->getMessage() . "</p>";

        // Optional: Logge den Fehler
        error_log($e->getMessage());
    }
};

