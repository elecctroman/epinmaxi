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
        $payload = $_POST;
        $gateway = new PayTRGateway([
            'merchant_id' => setting('paytr.merchant_id'),
            'merchant_key' => setting('paytr.merchant_key'),
            'merchant_salt' => setting('paytr.merchant_salt'),
        ]);
        if (!$gateway->verifyCallback($payload)) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $this->finalizeOrder($payload['merchant_oid'] ?? '', $payload['status'] === 'success', $payload);
        echo 'OK';
    }

    public function iyzico(): void
    {
        $token = $_POST['token'] ?? '';
        $signature = $_POST['signature'] ?? '';
        $gateway = new IyzicoGateway([
            'api_key' => setting('iyzico.api_key'),
            'secret_key' => setting('iyzico.secret_key'),
        ]);
        if (!$gateway->verifyCallback($token, ['signature' => $signature])) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $this->finalizeOrder($_POST['order_no'] ?? '', ($_POST['status'] ?? '') === 'success', $_POST);
        echo 'OK';
    }

    public function stripe(): void
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $gateway = new StripeGateway([
            'secret_key' => setting('stripe.secret_key'),
            'publishable_key' => setting('stripe.publishable_key'),
            'webhook_secret' => setting('stripe.webhook_secret'),
        ]);
        if (!$gateway->verifySignature($payload, $signature)) {
            http_response_code(403);
            echo 'Invalid signature';
            return;
        }
        $data = json_decode($payload, true);
        $orderNo = $data['data']['object']['metadata']['order_no'] ?? '';
        $status = ($data['type'] ?? '') === 'payment_intent.succeeded';
        $this->finalizeOrder($orderNo, $status, $data);
        echo 'OK';
    }

    protected function finalizeOrder(string $orderNo, bool $success, array $payload): void
    {
        if ($orderNo === '') {
            return;
        }

        $result = DB::transaction(function ($pdo) use ($orderNo, $success, $payload) {
            $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE order_no = :no FOR UPDATE');
            $orderStmt->execute(['no' => $orderNo]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
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

            return [
                'order_id' => $order['id'],
                'order_no' => $orderNo,
                'new_status' => $newPaymentStatus,
                'changed' => $statusChanged || ($success && !$wasPaid),
            ];
        });

        if ($result && $result['changed']) {
            DB::query('INSERT INTO logs (user_id, action, entity, entity_id, ip, created_at) VALUES (NULL,:action,:entity,:entity_id,:ip,NOW())', [
                'action' => 'webhook:' . $result['new_status'],
                'entity' => $result['order_no'],
                'entity_id' => $result['order_id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        }
    }
}
