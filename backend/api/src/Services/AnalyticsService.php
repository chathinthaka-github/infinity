<?php
declare(strict_types=1);

namespace App\Services;

/**
 * AnalyticsService
 * Lightweight event recorder that writes JSON lines to storage/analytics.log
 */
class AnalyticsService
{
    private string $logFile;

    public function __construct()
    {
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->logFile = $dir . '/analytics.log';
    }

    public function record(string $eventName, array $payload = []): bool
    {
        $entry = [
            'event' => $eventName,
            'payload' => $payload,
            'ts' => date('c'),
        ];
        return (bool)file_put_contents($this->logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
