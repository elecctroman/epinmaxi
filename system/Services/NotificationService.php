<?php
namespace System\Services;

use System\Core\Mailer;
use System\Services\Sms\MockSmsProvider;
use System\Services\Sms\SmsProviderInterface;

class NotificationService
{
    protected SmsProviderInterface $sms;

    public function __construct(?SmsProviderInterface $smsProvider = null)
    {
        $this->sms = $smsProvider ?? new MockSmsProvider();
    }

    public function orderCreated(string $email, array $payload): void
    {
        $this->sendEmail($email, 'Siparişiniz alındı - ' . $payload['order_no'], 'order_created', $payload);
    }

    public function orderPaid(string $email, array $payload): void
    {
        $this->sendEmail($email, 'Ödemeniz onaylandı - ' . $payload['order_no'], 'order_paid', $payload);
    }

    public function orderDelivered(string $email, array $payload): void
    {
        $this->sendEmail($email, 'Teslimat hazır - ' . $payload['order_no'], 'order_delivered', $payload);
    }

    public function sms(string $phone, string $message): void
    {
        if (!setting('sms.enabled')) {
            return;
        }
        $this->sms->send($phone, $message);
    }

    protected function sendEmail(string $to, string $subject, string $template, array $payload): void
    {
        $html = render_email($template, $payload);
        Mailer::send($to, $subject, $html, [
            'From' => setting('mail.from', 'no-reply@localhost'),
        ]);
    }
}
