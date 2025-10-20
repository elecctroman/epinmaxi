<?php
namespace System\Services\Payments;

class PayTRGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function charge(float $amount, string $currency, string $orderNo, array $customer): array
    {
        $merchantId = $this->config['merchant_id'] ?? '';
        $merchantKey = $this->config['merchant_key'] ?? '';
        $merchantSalt = $this->config['merchant_salt'] ?? '';
        if (!$merchantId || !$merchantKey || !$merchantSalt) {
            return [
                'status' => 'failed',
                'message' => 'PayTR gateway configuration missing.',
            ];
        }
        $hashStr = $merchantId . $customer['email'] . $amount . $merchantSalt;
        $signature = base64_encode(hash_hmac('sha256', $hashStr, $merchantKey, true));
        return [
            'status' => 'pending',
            'txn_id' => null,
            'raw_response' => [
                'redirect_url' => '/mock/paytr?order=' . urlencode($orderNo),
                'signature' => $signature,
            ],
        ];
    }

    public function verifyCallback(array $payload): bool
    {
        $hash = base64_encode(hash_hmac('sha256', $payload['merchant_oid'] . $this->config['merchant_salt'] . $payload['status'], $this->config['merchant_key'], true));
        return hash_equals($hash, $payload['hash'] ?? '');
    }
}
