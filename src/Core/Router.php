<?php

declare(strict_types=1);

namespace App\Core;

use Exception;
use Throwable;
use PDOException;
use PDO;

class Router
{
    private array $routes;
    private PDO $db;

    public function __construct(array $routes, PDO $db)
    {
        $this->routes = $routes;

        $this->db = $db;
    }

    /**
     * Main router dispatcher
     *
     * @throws Exception
     *
     * @return void
     */
    public function run() : void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            [$routeMethod, $routeUri, $handler] = $route;

            $params = [];

            if ($method === $routeMethod && $this->match($routeUri, $uri, $params)) {
                $this->callHandler($handler, $params);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        //echo json_encode(['error' => 'Route not found']);
        throw new Exception('Route not found', 404);
    }

    /**
     * Matches URI against route pattern with parameter extraction
     *
     * @param string $routeUri - route pattern ('/users/{id}')
     * @param string $uri      - request URI path ('/users/123')
     * @param array &$params   - [output] extracted route parameters by name
     *
     * @return bool true if URI matches pattern, false otherwise
     */
    private function match(string $routeUri, string $uri, array &$params) : bool
    {
        $routeParts = explode('/', trim($routeUri, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        $params = [];

        foreach ($routeParts as $index => $routePart) {
            if (preg_match('/^\{([^}]+)\}$/', $routePart, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $uriParts[$index];
            } elseif ($routePart !== $uriParts[$index]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create instance of controller and call specified method with route parameters
     *
     * @param string $handler - Controller@method notation
     * @param array $params   - route parameters extracted by match() method
     *
     * @throws Exception
     *
     * @return void
     */
    private function callHandler(string $handler, array $params) : void
    {
        [$controllerName, $methodName] = explode('@', $handler);
        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new Exception('Controller not found', 500);
        }

        $controller = new $controllerClass($this->db);

        if (!method_exists($controller, $methodName)) {
            throw new Exception('Method not found', 500);
        }

        $this->runRequest(function() use ($controller, $methodName, $params) {
            return $controller->$methodName($params);
        });
    }

    /**
     * Request handler with exceptions context
     *
     * @param callable $callback - request handler
     *
     * @return void
     */
    protected function runRequest(callable $callback) : void {
        try {
            $result = $callback();

            if ($result !== null) {
                $this->json($result, 200);
            }
        } catch (PDOException $exception) {
            error_log("DB " . $exception->getMessage());
            $this->json(['error' => 'Database error'], 500);
        } catch (Exception $exception) {
            /*
            $status = match($error->getCode()) {
                400, 422 => 400,
                401 => 401,
                404 => 404,
                default => 400
            };
            */
            $status = $exception->getCode() ?: 400;
            $this->json(['error' => $exception->getMessage()], $status);
        } catch (Throwable $exception) {
            $this->json(['error' => 'Internal server error'], 500);
        }
    }

    private function json(array $data, int $status = 200) : void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
