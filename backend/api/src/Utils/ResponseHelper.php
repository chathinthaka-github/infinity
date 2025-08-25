<?php
declare(strict_types=1);

namespace App\Utils;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class ResponseHelper
 * Small helper to write consistent JSON responses
 */
class ResponseHelper
{
    public static function json(Response $response, int $statusCode, array $payload): Response
    {
        $payload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}
