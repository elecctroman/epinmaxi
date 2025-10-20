<?php
namespace System\Services\Payments;

class IyzicoGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function charge(float $amount, string $currency, string $orderNo, array $customer): array
    {
        if (empty($this->config['api_key']) || empty($this->config['secret_key'])) {
            return [
                'status' => 'failed',
                'message' => 'Iyzico gateway configuration missing.',
            ];
        }
        $signature = base64_encode(hash_hmac('sha256', $orderNo . $amount . $currency, $this->config['secret_key'], true));
        return [
            'status' => 'pending',
            'txn_id' => null,
            'raw_response' => [
                'token' => $signature,
                'checkout_form_content' => '<div class="mock-iyzico">Ödeme formu bu alanda yüklenecek.</div>',
            ],
        ];
    }

    public function verifyCallback(string $token, array $payload): bool
    {
        $expected = base64_encode(hash_hmac('sha256', $token, $this->config['secret_key'], true));
        return hash_equals($expected, $payload['signature'] ?? '');
    }
}
