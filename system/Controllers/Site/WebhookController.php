<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Controller;
use System\Core\DB;
use System\Services\Delivery\DigitalDeliveryService;
use System\Services\Payments\PayTRGateway;
use System\Services\Payments\IyzicoGateway;
use System\Services\Payments\StripeGateway;

class WebhookController extends Controller
{
    public function paytr(): void
    {
        if (!rate_limit('webhook:paytr:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 30, 60)) {
            http_response_code(429);
            echo 'rate limited';
            return;
        }
        $payload = $_POST;
        $gateway = new PayTRGateway([
            'merchant_id' => setting('paytr.merchant_id'),
            'merchant_key' => setting('paytr.merchant_key'),
            'merchant_salt' => setting('paytr.merchant_salt'),
        ]);
        $signatureValid = $gateway->verifyCallback($payload);
        $this->logWebhook('paytr', $payload['merchant_oid'] ?? null, $payload, $signatureValid);
        if (!$signatureValid) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $this->finalizeOrder($payload['merchant_oid'] ?? '', $payload['status'] === 'success', $payload, 'paytr');
        echo 'OK';
    }

    public function iyzico(): void
    {
        if (!rate_limit('webhook:iyzico:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 30, 60)) {
            http_response_code(429);
            echo 'rate limited';
            return;
        }
        $token = $_POST['token'] ?? '';
        $signature = $_POST['signature'] ?? '';
        $gateway = new IyzicoGateway([
            'api_key' => setting('iyzico.api_key'),
            'secret_key' => setting('iyzico.secret_key'),
        ]);
        $signatureValid = $gateway->verifyCallback($token, ['signature' => $signature]);
        $this->logWebhook('iyzico', $_POST['order_no'] ?? $token, $_POST, $signatureValid);
        if (!$signatureValid) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $this->finalizeOrder($_POST['order_no'] ?? '', ($_POST['status'] ?? '') === 'success', $_POST, 'iyzico');
        echo 'OK';
    }

    public function stripe(): void
    {
        if (!rate_limit('webhook:stripe:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 60, 60)) {
            http_response_code(429);
            echo 'rate limited';
            return;
        }
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $gateway = new StripeGateway([
            'secret_key' => setting('stripe.secret_key'),
            'publishable_key' => setting('stripe.publishable_key'),
            'webhook_secret' => setting('stripe.webhook_secret'),
        ]);
        $signatureValid = $gateway->verifySignature($payload, $signature);
        $decoded = json_decode($payload, true) ?: [];
        $this->logWebhook('stripe', $decoded['id'] ?? null, $decoded, $signatureValid);
        if (!$signatureValid) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $data = $decoded;
        $orderNo = $data['data']['object']['metadata']['order_no'] ?? '';
        $status = ($data['type'] ?? '') === 'payment_intent.succeeded';
        $this->finalizeOrder($orderNo, $status, $data, 'stripe');
        echo 'OK';
    }

    protected function logWebhook(string $provider, ?string $reference, array $payload, bool $signatureValid): void
    {
        DB::query('INSERT INTO webhook_logs (provider, reference, payload, signature_valid, created_at) VALUES (:provider,:reference,:payload,:valid,NOW())', [
            'provider' => $provider,
            'reference' => $reference,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'valid' => $signatureValid ? 1 : 0,
        ]);
    }

    protected function finalizeOrder(string $orderNo, bool $success, array $payload, string $provider): void
    {
        if ($orderNo === '') {
            return;
        }

        $result = DB::transaction(function ($pdo) use ($orderNo, $success, $payload, $provider) {
            $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :no FOR UPDATE');
            $orderStmt->execute(['no' => $orderNo]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return null;
            }

            $existing = $pdo->prepare('SELECT id FROM webhook_logs WHERE reference = :ref AND provider = :provider AND processed_at IS NOT NULL LIMIT 1');
            $existing->execute([
                'ref' => $orderNo,
                'provider' => $provider,
            ]);
            if ($existing->fetchColumn()) {
                return null;
            }

            $wasPaid = $order['payment_status'] === 'paid';
            $newPaymentStatus = $success ? 'paid' : 'failed';
            $newOrderStatus = $success ? 'completed' : 'cancelled';

            if ($success && !$wasPaid) {
                $deliveryService = new DigitalDeliveryService(setting('app.encryption_key', 'demo-enc-key-32chars!!demo'));
                $itemsStmt = $pdo->prepare('SELECT oi.id, oi.product_id, oi.qty, oi.delivery_payload, p.type FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :order FOR UPDATE');
                $itemsStmt->execute(['order' => $order['id']]);
                $updatePayload = $pdo->prepare('UPDATE order_items SET delivery_payload = :payload WHERE id = :id');

                while ($item = $itemsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $currentPayload = $item['delivery_payload'] ? json_decode($item['delivery_payload'], true) : [];
                    if (!empty($currentPayload)) {
                        continue;
                    }

                    if ($item['type'] === 'account') {
                        $payloadData = $deliveryService->reserveAccounts((int) $item['product_id'], (int) $item['id'], (int) $item['qty']);
                    } else {
                        $payloadData = $deliveryService->reserveKeys((int) $item['product_id'], (int) $item['id'], (int) $item['qty']);
                    }

                    $updatePayload->execute([
                        'payload' => json_encode($payloadData, JSON_UNESCAPED_UNICODE),
                        'id' => $item['id'],
                    ]);
                }
            }

            if (!$success && $order['payment_status'] !== 'failed' && !empty($order['coupon_code'])) {
                $pdo->prepare('UPDATE coupons SET used_count = CASE WHEN used_count > 0 THEN used_count - 1 ELSE 0 END WHERE code = :code')->execute([
                    'code' => $order['coupon_code'],
                ]);
            }

            $statusChanged = ($order['payment_status'] !== $newPaymentStatus) || ($order['status'] !== $newOrderStatus);

            $pdo->prepare('UPDATE orders SET payment_status = :payment_status, status = :status, paid_at = CASE WHEN :markPaid = 1 THEN COALESCE(paid_at, NOW()) ELSE paid_at END WHERE id = :id')->execute([
                'payment_status' => $newPaymentStatus,
                'status' => $newOrderStatus,
                'markPaid' => $success ? 1 : 0,
                'id' => $order['id'],
            ]);

            $paymentUpdate = $pdo->prepare('UPDATE payments SET status = :status, raw_response_json = :raw WHERE order_id = :id ORDER BY id DESC LIMIT 1');
            $paymentUpdate->execute([
                'status' => $success ? 'success' : 'failed',
                'raw' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $order['id'],
            ]);

            if ($paymentUpdate->rowCount() === 0) {
                $pdo->prepare('INSERT INTO payments (order_id, provider, provider_txn_id, amount, currency, status, raw_response_json, created_at) VALUES (:order_id, :provider, NULL, :amount, :currency, :status, :raw, NOW())')->execute([
                    'order_id' => $order['id'],
                    'provider' => 'webhook',
                    'amount' => $order['grand_total'],
                    'currency' => $order['currency'],
                    'status' => $success ? 'success' : 'failed',
                    'raw' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);
            }

            $customerName = null;
            if (!empty($order['user_id'])) {
                $nameStmt = $pdo->prepare('SELECT name, phone FROM users WHERE id = :id LIMIT 1');
                $nameStmt->execute(['id' => $order['user_id']]);
                $userRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
                if ($userRow) {
                    $customerName = $userRow['name'];
                    $order['phone'] = $order['phone'] ?? $userRow['phone'] ?? null;
                }
            }

            return [
                'order_id' => $order['id'],
                'order_no' => $orderNo,
                'new_status' => $newPaymentStatus,
                'changed' => $statusChanged || ($success && !$wasPaid),
                'provider' => $provider,
                'email' => $order['email'],
                'total' => $order['grand_total'],
                'currency' => $order['currency'],
                'customer' => [
                    'name' => $customerName,
                    'phone' => $order['phone'] ?? null,
                ],
            ];
        });

        if ($result && $result['changed']) {
            DB::query('INSERT INTO logs (user_id, action, entity, entity_id, ip, created_at) VALUES (NULL,:action,:entity,:entity_id,:ip,NOW())', [
                'action' => 'webhook:' . $result['new_status'],
                'entity' => $result['order_no'],
                'entity_id' => $result['order_id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            DB::query('UPDATE webhook_logs SET processed_at = NOW() WHERE reference = :ref AND provider = :provider', [
                'ref' => $result['order_no'],
                'provider' => $result['provider'],
            ]);
            $notifier = new \System\Services\NotificationService();
            if ($result['new_status'] === 'paid') {
                $notifier->orderPaid($result['email'], [
                    'order_no' => $result['order_no'],
                    'total' => $result['total'],
                    'currency' => $result['currency'],
                    'payment_method' => $result['provider'],
                    'customer' => $result['customer'],
                ]);
                $notifier->orderDelivered($result['email'], [
                    'order_no' => $result['order_no'],
                    'order_url' => rtrim(setting('app.url', '/'), '/') . '/siparis/' . $result['order_no'],
                    'customer' => $result['customer'],
                ]);
                if (!empty($result['customer']['phone'])) {
                    $notifier->sms($result['customer']['phone'], 'Siparisiniz hazir: ' . $result['order_no']);
                }
            }
        }
    }
}
