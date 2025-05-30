<?php

namespace App\Core;

use Exception;

class Router {
    protected array $routes = [];

    /**
     * Fügt eine Route zur Routing-Tabelle hinzu.
     *
     * @param string $method HTTP-Methode (GET, POST, etc.)
     * @param string $uri Der URI-Pfad (z.B. /users, /products/{id})
     * @param string $controller Klassenname des Controllers
     * @param string $action Methode im Controller
     */
    public function addRoute(string $method, string $uri, string $controller, string $action): void {
        // Normalisiere den URI (Entferne führende/trailing Slashes für Konsistenz)
        $uri = trim($uri, '/');
        $this->routes[$method][$uri] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Sucht die passende Route und führt die Controller-Aktion aus.
     *
     * @param string $requestMethod Die HTTP-Methode der Anfrage
     * @param string $requestUri Der angeforderte URI
     * @throws Exception Wenn keine Route gefunden wird oder Controller/Methode nicht existiert.
     */
    public function dispatch(string $requestMethod, string $requestUri): void {
        // Normalisiere den angeforderten URI
        $requestUri = trim($requestUri, '/');

        // Prüfe, ob eine Route für die Methode und URI existiert
        if (isset($this->routes[$requestMethod][$requestUri])) {
            $route = $this->routes[$requestMethod][$requestUri];
            $controllerName = $route['controller'];
            $actionName = $route['action'];

            // Prüfe, ob die Controller-Klasse existiert
            if (class_exists($controllerName)) {
                $controllerInstance = new $controllerName(); // Erstelle eine Instanz des Controllers

                // Prüfe, ob die Methode im Controller existiert
                if (method_exists($controllerInstance, $actionName)) {
                    // Rufe die Controller-Methode auf
                    $controllerInstance->$actionName();
                } else {
                    throw new Exception("Methode {$actionName} im Controller {$controllerName} nicht gefunden.");
                }
            } else {
                throw new Exception("Controller-Klasse {$controllerName} nicht gefunden.");
            }
        } else {
            throw new Exception("Keine Route für {$requestMethod} {$requestUri} gefunden.", 404);
        }
    }
}
