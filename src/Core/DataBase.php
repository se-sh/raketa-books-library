<?php

declare(strict_types=1);

namespace App\Core;

use Exception;
use PDO;
use PDOException;

class DataBase
{
    /**
     * Connect to database
     *
     * @return PDO
     *
     * @throws Exception
     */
    public static function connect() : PDO
    {
        $env = require __DIR__ . '/../../.env.php';

        if (empty($env['DB_HOST']) || empty($env['DB_NAME']) || empty($env['DB_USER']) || empty($env['DB_PASS'])) {
            throw new Exception('DB config missing', 400);
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8", $env['DB_USER'], $env['DB_PASS'], $options);
    }
}
