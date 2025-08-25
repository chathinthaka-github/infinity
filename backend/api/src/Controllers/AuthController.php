<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Services\AuthService;
use App\Utils\Validator;
use App\Utils\ResponseHelper;
use PDO;

/**
 * Class AuthController
 * Minimal register/login/me endpoints
 */
class AuthController
{
    private PDO $db;
    private AuthService $authService;

    public function __construct($container)
    {
        // container in index.php used container->set('db', ...) so retrieve
        $this->db = $container->get('db');
        $this->authService = new AuthService();
    }

    public function register(Request $request, Response $response): Response
    {
        $body = (array)$request->getParsedBody();
        $errors = Validator::validateRegister($body);
        if (!empty($errors)) {
            return ResponseHelper::json($response, 422, ['success' => false, 'errors' => $errors]);
        }

        $userModel = new User($this->db);
        // check exists
        if ($userModel->findByEmail($body['email'])) {
            return ResponseHelper::json($response, 409, ['success' => false, 'error' => 'Email already registered']);
        }

        // hash password
        $password = $body['password'];
        if (defined('PASSWORD_ARGON2ID')) {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
        } else {
            $cost = (int)($_ENV['BCRYPT_ROUNDS'] ?? 12);
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
        }

        $userId = $userModel->create([
            'email' => $body['email'],
            'password_hash' => $hash,
            'full_name' => $body['full_name'] ?? null,
            'whatsapp_number' => $body['whatsapp_number'] ?? null,
            'role' => 'student'
        ]);

        if ($userId <= 0) {
            return ResponseHelper::json($response, 500, ['success' => false, 'error' => 'Failed to create user']);
        }

        $user = $userModel->findById($userId);
        $token = $this->authService->createToken($user);

        return ResponseHelper::json($response, 201, [
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
                'expires_in' => (int)($_ENV['JWT_EXPIRY'] ?? 86400)
            ]
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array)$request->getParsedBody();
        $errors = Validator::validateLogin($body);
        if (!empty($errors)) {
            return ResponseHelper::json($response, 422, ['success' => false, 'errors' => $errors]);
        }

        $userModel = new User($this->db);
        $user = $userModel->findByEmail($body['email']);
        if (!$user) {
            return ResponseHelper::json($response, 401, ['success' => false, 'error' => 'Invalid credentials']);
        }

        // verify password
        $hash = $user['password_hash'] ?? '';
        if (!password_verify($body['password'], $hash)) {
            return ResponseHelper::json($response, 401, ['success' => false, 'error' => 'Invalid credentials']);
        }

        $token = $this->authService->createToken($user);

        return ResponseHelper::json($response, 200, [
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
                'expires_in' => (int)($_ENV['JWT_EXPIRY'] ?? 86400)
            ]
        ]);
    }

    public function getCurrentUser(Request $request, Response $response): Response
    {
        $userId = (int)$request->getAttribute('user_id');
        if (!$userId) {
            return ResponseHelper::json($response, 401, ['success' => false, 'error' => 'Unauthorized']);
        }

        $userModel = new User($this->db);
        $user = $userModel->findById($userId);
        if (!$user) {
            return ResponseHelper::json($response, 404, ['success' => false, 'error' => 'User not found']);
        }

        return ResponseHelper::json($response, 200, ['success' => true, 'data' => $user]);
    }
}
