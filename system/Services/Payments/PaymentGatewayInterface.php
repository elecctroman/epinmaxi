<?php
namespace System\Services\Payments;

interface PaymentGatewayInterface
{
    public function charge(float $amount, string $currency, string $orderNo, array $customer): array;
}
