<?php
namespace System\Core;

class Mailer
{
    public static function send(string $to, string $subject, string $html, array $headers = []): bool
    {
        $from = $headers['From'] ?? setting('mail.from', 'no-reply@localhost');
        $headers = array_merge([
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'From' => $from,
        ], $headers);

        $smtpHost = setting('mail.host');
        if ($smtpHost) {
            return self::sendViaSmtp($smtpHost, (int) setting('mail.port', 587), $to, $subject, $html, $headers);
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }

        return mail($to, $subject, $html, $headerString);
    }

    protected static function sendViaSmtp(string $host, int $port, string $to, string $subject, string $body, array $headers): bool
    {
        $encryption = strtolower((string) setting('mail.encryption', 'tls'));
        $username = setting('mail.username');
        $password = setting('mail.password');
        $timeout = 15;
        $transport = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @fsockopen($transport, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return false;
        }

        $read = function () use ($socket) {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $command = function (string $cmd) use ($socket, $read) {
            fwrite($socket, $cmd . "\r\n");
            return $read();
        };

        $read();
        $command('EHLO ' . ($host ?: 'localhost'));
        if ($encryption === 'tls') {
            $command('STARTTLS');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $command('EHLO ' . ($host ?: 'localhost'));
        }
        if ($username && $password) {
            $command('AUTH LOGIN');
            $command(base64_encode($username));
            $command(base64_encode($password));
        }

        $command('MAIL FROM: <' . ($headers['From'] ?? $username ?? 'no-reply@localhost') . '>');
        $command('RCPT TO: <' . $to . '>');
        $command('DATA');

        $headerLines = '';
        foreach ($headers as $key => $value) {
            $headerLines .= $key . ': ' . $value . "\r\n";
        }
        $message = $headerLines . 'To: ' . $to . "\r\n" . 'Subject: ' . $subject . "\r\n" . "\r\n" . $body . "\r\n";
        $command($message . '.');
        $command('QUIT');
        fclose($socket);
        return true;
    }
}
