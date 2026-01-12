<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use PDOException;

class Auth
{
    private PDO $db;
    private User $userModel;

    private string $jwtSecret;
    private string $jwtIssuer;
    private int $jwtLifetime;

    public function __construct(PDO $db)
    {
        $this->userModel = new User($db);

        $env = require __DIR__ . '/../../.env.php';

        if (empty($env['JWT_SECRET']) || empty($env['JWT_ISSUER']) || empty($env['JWT_LIFETIME'])) {
            throw new Exception('config missing', 400);
        }

        $this->jwtSecret = $env['JWT_SECRET'];
        $this->jwtIssuer = $env['JWT_ISSUER'];
        $this->jwtLifetime = $env['JWT_LIFETIME'];
    }

    /**
     * Authenticate user from Bearer token in Authorization header
     *
     * @return array - decoded JWT payload containing user claim
     *
     * @throws Exception
     */
    public function authenticate() : array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (strpos($authHeader, 'Bearer ') !== 0) {
            throw new Exception('Authorization header missing or invalid', 401);
        }

        // 'Bearer '
        $token = substr($authHeader, 7);

        return $this->verifyToken($token);
    }

    /**
     * Authenticate user credentials and generate JWT access token
     *
     * @param string $login    - user login
     * @param string $password - user pass
     *
     * @return array JSON with token, user ID, login
     *
     * @throws Exception
     */
    public function login(string $login, string $password) : array
    {
        $user = $this->userModel->getUserByLogin($login);

        if ($user === null) {
            throw new Exception('Invalid login or password', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid login or password', 401);
        }

        $token = $this->generateToken((int) $user['id'], $user['login']);

        return [
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'login' => $user['login'],
            ],
        ];
    }

    public function getToken(int $userId, string $login)
    {
        return $this->generateToken($userId, $login);
    }

    /**
     * Verify and decode JWT token signature + claims
     *
     * @param string $token - JWT toke
     *
     * @return array $decoded
     */
    public function verifyToken(string $token) : array
    {
        $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

        return (array) $decoded;
    }

    /**
     * Generate serialized JWT token for user authentication
     *
     * @param int    $userId - unique user identifier
     * @param string $login  - user login for identification
     *
     * @return string - JWT token
     */
    private function generateToken(int $userId, string $login) : string
    {
        $now = time();
        $exp = $now + $this->jwtLifetime;

        $payload = [
            'iss' => $this->jwtIssuer,
            'iat' => $now,
            'exp' => $exp,
            'sub' => $userId,
            'login' => $login,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
