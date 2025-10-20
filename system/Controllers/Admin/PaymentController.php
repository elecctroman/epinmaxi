<?php
namespace System\Controllers\Admin;

use System\Core\DB;

class PaymentController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-payments');
        $payments = DB::query('SELECT pay.*, o.order_no FROM payments pay INNER JOIN orders o ON o.id = pay.order_id ORDER BY pay.created_at DESC LIMIT 100')->fetchAll();
        $this->render('payments/index', [
            'title' => 'Ödeme Kayıtları',
            'payments' => $payments,
        ]);
    }

    public function show(int $id): void
    {
        $this->enforce('manage-payments');
        $payment = DB::query('SELECT pay.*, o.order_no, o.email FROM payments pay INNER JOIN orders o ON o.id = pay.order_id WHERE pay.id = :id', ['id' => $id])->fetch();
        if (!$payment) {
            http_response_code(404);
            exit('Kayıt bulunamadı');
        }
        $webhooks = DB::query('SELECT * FROM webhook_logs WHERE provider = :provider AND reference = :ref ORDER BY created_at DESC', [
            'provider' => $payment['provider'],
            'ref' => $payment['provider_txn_id'],
        ])->fetchAll();
        $this->render('payments/show', [
            'title' => 'Ödeme Detayı',
            'payment' => $payment,
            'webhooks' => $webhooks,
        ]);
    }
}
