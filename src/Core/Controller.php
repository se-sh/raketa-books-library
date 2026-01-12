<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

class Controller
{
    /**
     * Send JSON response with specified HTTP status code
     *
     * @param mixed $data       - response data to encode as JSON
     * @param int   $statusCode - (optional) HTTP status code (default: 200)
     *
     * @return void
     */
    protected function json(mixed $data, int $statusCode = 200) : void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Read and parse JSON request body from php://input
     *
     * @return array $data - parsed JSON data
     *
     * @throws Exception
     */
    protected function getJsonInput() : array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            throw new Exception('Cant read request', 400);
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new Exception('JSON expected object or array', 400);
        }

        return $data;
    }

    /**
     * Loads and validates uploaded text file content
     *
     * @param array $file - attached file
     *
     * @return string $content - content of file
     *
     * @throws Exception
     */
    protected function loadFile(array $file) : string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error', 400);
        }

        $allowedTypes = ['text/plain'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Only .TXT files allowed', 400);
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            throw new Exception('Can not read file content', 400);
        }

        return $content;
    }
}
