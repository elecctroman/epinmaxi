<?php
namespace System\Core;

class Mailer
{
    public static function send(string $to, string $subject, string $html, array $headers = []): bool
    {
        $headers = array_merge([
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'From' => 'no-reply@localhost'
        ], $headers);

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }

        return mail($to, $subject, $html, $headerString);
    }
}
