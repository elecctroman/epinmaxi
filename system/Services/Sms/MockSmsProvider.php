<?php
namespace System\Services\Sms;

class MockSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): bool
    {
        file_put_contents(__DIR__ . '/../../../storage/logs/sms.log', date('c') . "|" . $phone . "|" . $message . PHP_EOL, FILE_APPEND);
        return true;
    }
}
