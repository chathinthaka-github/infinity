<?php
declare(strict_types=1);

namespace App\Utils;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

/**
 * Logger helper - returns a configured Monolog logger instance.
 */
class Logger
{
    private static ?MonoLogger $logger = null;

    public static function get(string $name = 'app'): MonoLogger
    {
        if (self::$logger !== null) {
            return self::$logger;
        }

        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . '/app.log';
        $logger = new MonoLogger($name);
        $logger->pushHandler(new StreamHandler($path, MonoLogger::DEBUG));
        self::$logger = $logger;
        return $logger;
    }
}
