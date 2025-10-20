<?php
namespace System\Services\Payments;

class StripeGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function charge(float $amount, string $currency, string $orderNo, array $customer): array
    {
        if (empty($this->config['secret_key'])) {
            return [
                'status' => 'failed',
                'message' => 'Stripe secret anahtarı bulunamadı.',
            ];
        }
        $intentId = 'pi_' . substr(hash('sha256', $orderNo . microtime(true)), 0, 24);
        return [
            'status' => 'pending',
            'txn_id' => $intentId,
            'raw_response' => [
                'client_secret' => $intentId . '_secret',
                'publishable_key' => $this->config['publishable_key'] ?? 'pk_test_placeholder',
            ],
        ];
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        if (empty($this->config['webhook_secret'])) {
            return false;
        }
        $signed = hash_hmac('sha256', $payload, $this->config['webhook_secret']);
        return hash_equals($signed, $signature);
    }
}
