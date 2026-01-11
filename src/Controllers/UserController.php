<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Auth;
use App\Models\User;
use Exception;
use PDO;

class UserController extends Controller
{
    private Auth $authModel;
    private User $userModel;

    public function __construct(PDO $db)
    {
        $this->authModel = new Auth($db);
        $this->userModel = new User($db);
    }

    /**
     * Register new user
     *
     * @throws Exception
     *
     * @return void
     */
    public function register() : void
    {
        $data = $this->getJsonInput();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if ($login === '' || $password === '' || $passwordConfirm === '') {
            throw new Exception('login, password and password_confirm required', 400);
        }

        $result = $this->userModel->createUser($login, $password, $passwordConfirm);

        $this->json([
            'token' => $result['token'],
            'user'  => $result['user'],
        ], 201);
    }

    /**
     * Show all users
     *
     * @return void
     */
    public function index() : void
    {
        $users = $this->userModel->getAllUsers();

        $this->json(['data' => $users], 200);
    }

    /**
     * Grant access to library to user by user ID
     *
     * @param array $params - route parameters containing user ID who accept access
     *
     * @throws Exception
     *
     * @return void
     */
    public function grant(array $params) : void
    {
        $user = $this->authModel->authenticate();
        $ownerId = $user['sub'];

        $targetId = (int)$params['id'];

        if ($targetId === null || $ownerId === null) {
            throw new Exception('Missing ID', 400);
        }

        $this->userModel->grantAccess($ownerId, $targetId);

        $this->json(['message' => 'Access granted'], 200);
    }


}
