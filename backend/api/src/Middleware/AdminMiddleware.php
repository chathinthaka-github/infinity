<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AdminMiddleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // First run AuthMiddleware
        $authMiddleware = new AuthMiddleware($this->container);
        $response = $authMiddleware($request, new class($handler) implements RequestHandlerInterface {
            private $handler;
            public function __construct($handler) { $this->handler = $handler; }
            public function handle(Request $request): Response {
                $userRole = $request->getAttribute('user_role');

                if ($userRole !== 'admin') {
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['error' => 'Admin access required']));
                    return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
                }

                return $this->handler->handle($request);
            }
        });

        return $response;
    }
}