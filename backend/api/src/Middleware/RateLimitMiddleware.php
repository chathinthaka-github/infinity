<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use App\Utils\ResponseHelper;

/**
 * RateLimitMiddleware
 * Simple rate limiter using APCu when available. If APCu is not present, no-op (dev mode).
 */
class RateLimitMiddleware
{
    private int $limit;
    private int $window; // seconds

    public function __construct(int $limit = 120, int $window = 60)
    {
        $this->limit = $limit;
        $this->window = $window;
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        // If APCu available, use it; otherwise skip (safe for dev)
        if (!extension_loaded('apcu')) {
            return $handler->handle($request);
        }

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'anon';
        $key = 'rl_' . md5($ip);

        $now = time();
        $data = apcu_fetch($key);
        if ($data === false) {
            $data = ['count' => 1, 'until' => $now + $this->window];
            apcu_store($key, $data, $this->window);
        } else {
            if ($data['until'] < $now) {
                $data = ['count' => 1, 'until' => $now + $this->window];
                apcu_store($key, $data, $this->window);
            } else {
                $data['count']++;
                apcu_store($key, $data, $data['until'] - $now);
            }
        }

        if ($data['count'] > $this->limit) {
            $retry = $data['until'] - $now;
            $body = ['success' => false, 'error' => 'Rate limit exceeded', 'retry_in_seconds' => $retry];
            return ResponseHelper::json(new \Slim\Psr7\Response(), 429, $body);
        }

        return $handler->handle($request);
    }
}
