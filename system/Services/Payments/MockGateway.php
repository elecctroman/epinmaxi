<?php
namespace System\Services\Payments;

class MockGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, string $currency, string $orderNo, array $customer): array
    {
        return [
            'status' => 'success',
            'txn_id' => 'MOCK-' . strtoupper(bin2hex(random_bytes(4))),
            'raw_response' => [
                'message' => 'Mock payment approved',
                'amount' => $amount,
                'currency' => $currency,
                'order' => $orderNo,
                'customer' => $customer,
            ],
        ];
    }
}
