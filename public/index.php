<?php

declare(strict_types=1);

use App\Core\DataBase;
use App\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $routes = require __DIR__ . '/../src/config/routes.php';

    $db = DataBase::connect();

    $router = new Router($routes, $db);
    $router->run();
} catch (PDOException $exception) {
    error_log('DB ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
} catch (Exception $exception) {
    $status = $exception->getCode() ?: 500;
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Startup error']);
}
