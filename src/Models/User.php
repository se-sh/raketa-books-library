<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Auth;
use Exception;
use PDO;

class User
{
    public int $id;
    public string $login;
    public string $password_hash;
    public string $created_at;
    public ?string $updated_at;

    private PDO $db;

    private Auth $authModel;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create new user account with login and hashed password
     *
     * @param string $login        - unique user login identifier
     * @param string $passwordHash - bcrypt password hash (60 chars)
     *
     * @return int - generated user ID
     *
     * @throws PDOException
     */
    public function create(string $login, string $passwordHash) : int
    {
        $sql = <<<'SQL'
        INSERT INTO `users`
            (login, password_hash)
        VALUES
            (:login, :password_hash)
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':login' => $login,
            ':password_hash' => $passwordHash,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get user record by login
     *
     * @param string $login
     *
     * @return null|array user record or null if not found
     *
     * @throws PDOException
     */
    public function getUserByLogin(string $login) : ?array
    {
        $sql = <<<'SQL'
        SELECT
            `id`,
            `login`,
            `password_hash`,
            `created`,
            `updated`
        FROM
            `users`
        WHERE
            `login` = :login
        LIMIT 1
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':login' => $login,
        ]);

        return $query->fetch() ?: null;
    }

    /**
     * Get list of all users (ID + login)
     *
     * @return array - list of user
     *
     * @throws PDOException
     */
    public function getAllUsers() : array
    {
        $sql = <<<'SQL'
        SELECT
            `id`,
            `login`
        FROM
            `users`
        ORDER BY
            `id` ASC
        SQL;

        $query = $this->db->query($sql);

        return $query->fetchAll();
    }

    /**
     * Register new user account with automatic JWT token generation
     *
     * @param string $login
     * @param string $password
     * @param string $passwordConfirm
     *
     * @return array - token, user ID, user login
     *
     * @throws Exception
     */
    public function createUser(string $login, string $password, string $passwordConfirm) : array
    {
        if ($password !== $passwordConfirm) {
            throw new Exception('Password confirmation does not match', 422);
        }

        $existingUser = $this->getUserByLogin($login);

        if ($existingUser !== null) {
            throw new Exception('User already exists', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->create($login, $passwordHash);

        $userData = [
            'id' => $userId,
            'login' => $login,
        ];

        //$token = $this->generateToken((int)$userId, $login);
        $this->authModel = new Auth($this->db);
        $token = $this->authModel->getToken((int) $userId, $login);

        return [
            'token' => $token,
            'user' => $userData,
        ];
    }

    /**
     * Authenticate user and returns JWT access token
     *
     * @param string $login
     * @param string $password - plaintext password to verify
     *
     * @return array - user token, user ID, user login
     *
     * @throws Exception
     */
    public function login(string $login, string $password) : array
    {
        $user = $this->getUserByLogin($login);

        if ($user === null) {
            throw new Exception('Invalid login or password', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid login or password', 401);
        }

        //$token = $this->generateToken((int)$user['id'], $user['login']);
        $this->authModel = new Auth($this->db);
        $token = $this->authModel->getToken((int) $user['id'], $login);

        return [
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'login' => $user['login'],
            ],
        ];
    }

    /**
     * Grant access to view owner's books
     *
     * @param int $ownerId  - user ID who allow access
     * @param int $targetId - user ID who can viewe owner's book
     *
     * @return void
     *
     * @throws PDOException
     */
    public function grantAccess(int $ownerId, int $targetId) : void
    {
        $sql = <<<'SQL'
        INSERT IGNORE INTO `library_access`
            (`owner_id`, `target_id`)
        VALUES
            (:owner_id, :target_id)
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':owner_id' => $ownerId,
            ':target_id' => $targetId,
        ]);
    }
}
