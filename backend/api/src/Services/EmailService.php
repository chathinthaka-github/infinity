<?php
declare(strict_types=1);

namespace App\Services;

/**
 * EmailService
 * Minimal email sending wrapper. Uses PHP mail() by default and writes to a local
 * log if sending is not configured for production.
 */
class EmailService
{
    private string $fromName;
    private string $fromEmail;

    public function __construct()
    {
        $this->fromName = $_ENV['APP_NAME'] ?? 'App';
        $this->fromEmail = $_ENV['SMTP_USERNAME'] ?? 'noreply@localhost';
    }

    /**
     * Send a simple text/html email.
     * Returns ['success' => true] or ['success' => false, 'error' => '...']
     */
    public function send(string $to, string $subject, string $htmlBody, array $headers = []): array
    {
        $defaultHeaders = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
        ];
        $hdr = implode("\r\n", array_merge($defaultHeaders, $headers));

        // Try PHP mail
        try {
            $ok = @mail($to, $subject, $htmlBody, $hdr);
        } catch (\Throwable $e) {
            $ok = false;
        }

        if ($ok) {
            return ['success' => true];
        }

        // fallback: log message to storage/email.log
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $entry = [
            'to' => $to,
            'subject' => $subject,
            'body' => $htmlBody,
            'headers' => $hdr,
            'ts' => date('c'),
        ];
        file_put_contents($logDir . '/email.log', json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

        return ['success' => false, 'error' => 'Mail not sent; logged to storage/logs/email.log'];
    }
}
