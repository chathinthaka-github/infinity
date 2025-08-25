<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use App\Services\AuthService;
use App\Utils\ResponseHelper;

/**
 * Class AuthMiddleware
 * Verifies Bearer token and sets request attributes
 */
class AuthMiddleware
{
    private AuthService $authService;

    public function __construct($container = null)
    {
        $this->authService = new AuthService();
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return ResponseHelper::json((new \Slim\Psr7\Response()), 401, ['success' => false, 'error' => 'Missing or invalid Authorization header']);
        }

        $token = $matches[1];
        try {
            $payload = $this->authService->verifyToken($token);
            // set attributes for controllers
            $request = $request->withAttribute('user_id', (int)($payload->sub ?? 0));
            $request = $request->withAttribute('role', $payload->role ?? 'student');
            return $handler->handle($request);
        } catch (\Exception $e) {
            return ResponseHelper::json((new \Slim\Psr7\Response()), 401, ['success' => false, 'error' => 'Invalid or expired token']);
        }
    }
}
