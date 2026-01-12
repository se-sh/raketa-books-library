<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Auth;
use Exception;
use PDO;

/**
 * Class AuthController for authentication requests (user login).
 */
class AuthController extends Controller
{
    private Auth $authModel;

    public function __construct(PDO $db)
    {
        $this->authModel = new Auth($db);
    }

    /**
     * User login
     *
     * @return void
     */
    public function login() : void
    {
        $data = $this->getJsonInput();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if ($login === '' || $password === '') {
            throw new Exception('Login and password required', 400);
        }

        $result = $this->authModel->login($login, $password);

        $this->json([
            'token' => $result['token'],
            'user' => $result['user'],
        ], 200);
    }
}
