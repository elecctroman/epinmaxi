<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;
use System\Services\Delivery\DigitalDeliveryService;

class OrderController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-orders');
        $query = 'SELECT * FROM orders WHERE 1=1';
        $params = [];
        if (!empty($_GET['status'])) {
            $query .= ' AND status = :status';
            $params['status'] = $_GET['status'];
        }
        if (!empty($_GET['payment_status'])) {
            $query .= ' AND payment_status = :payment_status';
            $params['payment_status'] = $_GET['payment_status'];
        }
        if (!empty($_GET['q'])) {
            $query .= ' AND (order_no LIKE :q OR email LIKE :q)';
            $params['q'] = '%' . $_GET['q'] . '%';
        }
        $query .= ' ORDER BY created_at DESC LIMIT 100';
        $orders = DB::query($query, $params)->fetchAll();
        $this->render('orders/index', [
            'title' => 'Siparişler',
            'orders' => $orders,
        ]);
    }

    public function show(int $id): void
    {
        $this->enforce('manage-orders');
        $order = DB::query('SELECT * FROM orders WHERE id = :id', ['id' => $id])->fetch();
        if (!$order) {
            Flash::set('Sipariş bulunamadı.', 'danger');
            $this->redirect('/admin/siparisler');
        }
        $items = DB::query('SELECT oi.*, p.title, p.type FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :id', ['id' => $id])->fetchAll();
        $payments = DB::query('SELECT * FROM payments WHERE order_id = :id ORDER BY created_at DESC', ['id' => $id])->fetchAll();
        $this->render('orders/show', [
            'title' => 'Sipariş Detayı',
            'order' => $order,
            'items' => $items,
            'payments' => $payments,
        ]);
    }

    public function updateStatus(int $id): void
    {
        $this->enforce('manage-orders');
        $order = DB::query('SELECT * FROM orders WHERE id = :id', ['id' => $id])->fetch();
        if (!$order) {
            Flash::set('Sipariş bulunamadı.', 'danger');
            $this->redirect('/admin/siparisler');
        }
        $status = $_POST['status'] ?? $order['status'];
        $paymentStatus = $_POST['payment_status'] ?? $order['payment_status'];
        DB::query('UPDATE orders SET status = :status, payment_status = :payment_status WHERE id = :id', [
            'status' => $status,
            'payment_status' => $paymentStatus,
            'id' => $id,
        ]);
        $this->audit('update', 'order', $id, ['status' => $status, 'payment_status' => $paymentStatus]);
        Flash::set('Sipariş durumu güncellendi.', 'success');
        $this->redirect('/admin/siparisler/' . $id);
    }

    public function deliver(int $id): void
    {
        $this->enforce('manage-orders');
        $order = DB::query('SELECT * FROM orders WHERE id = :id', ['id' => $id])->fetch();
        if (!$order) {
            Flash::set('Sipariş bulunamadı.', 'danger');
            $this->redirect('/admin/siparisler');
        }
        $encryptionKey = setting('app.encryption_key');
        if (!$encryptionKey) {
            Flash::set('Teslimat için şifreleme anahtarı gereklidir.', 'danger');
            $this->redirect('/admin/siparisler/' . $id);
        }
        $service = new DigitalDeliveryService($encryptionKey);
        try {
            DB::transaction(function () use ($id, $service) {
                $items = DB::query('SELECT oi.id, oi.product_id, oi.qty, p.type FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :id', ['id' => $id])->fetchAll();
                foreach ($items as $item) {
                    if ($item['type'] === 'account') {
                        $service->releaseAccountsByOrderItem($item['id']);
                        $payload = $service->reserveAccounts($item['product_id'], $item['id'], $item['qty']);
                    } else {
                        $service->releaseKeysByOrderItem($item['id']);
                        $payload = $service->reserveKeys($item['product_id'], $item['id'], $item['qty']);
                    }
                    DB::query('UPDATE order_items SET delivery_payload = :payload WHERE id = :id', [
                        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'id' => $item['id'],
                    ]);
                }
                DB::query('UPDATE orders SET status = "completed", payment_status = "paid", paid_at = COALESCE(paid_at, NOW()) WHERE id = :id', ['id' => $id]);
            });
            $this->audit('deliver', 'order', $id);
            Flash::set('Dijital teslimat tamamlandı.', 'success');
        } catch (\Throwable $e) {
            Flash::set('Teslimat başarısız: ' . $e->getMessage(), 'danger');
        }
        $this->redirect('/admin/siparisler/' . $id);
    }

    public function refund(int $id): void
    {
        $this->enforce('manage-orders');
        $order = DB::query('SELECT * FROM orders WHERE id = :id', ['id' => $id])->fetch();
        if (!$order) {
            Flash::set('Sipariş bulunamadı.', 'danger');
            $this->redirect('/admin/siparisler');
        }
        $encryptionKey = setting('app.encryption_key');
        $service = $encryptionKey ? new DigitalDeliveryService($encryptionKey) : null;
        try {
            DB::transaction(function () use ($id, $order, $service) {
                $items = DB::query('SELECT oi.id, oi.product_id, p.type FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :id', ['id' => $id])->fetchAll();
                foreach ($items as $item) {
                    if ($service) {
                        if ($item['type'] === 'account') {
                            $service->releaseAccountsByOrderItem($item['id']);
                        } else {
                            $service->releaseKeysByOrderItem($item['id']);
                        }
                    }
                    DB::query('UPDATE order_items SET delivery_payload = NULL WHERE id = :id', ['id' => $item['id']]);
                }
                DB::query('UPDATE orders SET status = "cancelled", payment_status = "refunded" WHERE id = :id', ['id' => $id]);
                $payment = DB::query('SELECT amount, currency FROM payments WHERE order_id = :id ORDER BY created_at DESC LIMIT 1', ['id' => $id])->fetch();
                if ($order['user_id'] && $payment) {
                    $wallet = DB::query('SELECT id FROM wallets WHERE user_id = :user', ['user' => $order['user_id']])->fetchColumn();
                    if (!$wallet) {
                        DB::query('INSERT INTO wallets (user_id, balance, created_at) VALUES (:user, 0, NOW())', ['user' => $order['user_id']]);
                        $wallet = DB::pdo()->lastInsertId();
                    }
                    DB::query('UPDATE wallets SET balance = balance + :amount WHERE id = :id', ['amount' => $payment['amount'], 'id' => $wallet]);
                    DB::query('INSERT INTO wallet_transactions (wallet_id, type, amount, note, created_at) VALUES (:wallet,"credit",:amount,:note,NOW())', [
                        'wallet' => $wallet,
                        'amount' => $payment['amount'],
                        'note' => 'Sipariş #' . $order['order_no'] . ' iade',
                    ]);
                }
            });
            $this->audit('refund', 'order', $id);
            Flash::set('Sipariş iade edildi.', 'success');
        } catch (\Throwable $e) {
            Flash::set('İade gerçekleştirilemedi: ' . $e->getMessage(), 'danger');
        }
        $this->redirect('/admin/siparisler/' . $id);
    }
}
