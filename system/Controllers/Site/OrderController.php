<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Helpers\Flash;

class OrderController extends Controller
{
    public function show(string $orderNo): void
    {
        $order = DB::query('SELECT * FROM orders WHERE order_no = :no LIMIT 1', ['no' => $orderNo])->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            http_response_code(404);
            echo 'Sipariş bulunamadı';
            return;
        }
        if (!$this->authorized($order)) {
            Flash::set('Bu siparişi görüntüleme yetkiniz yok.', 'danger');
            $this->redirect('/giris');
            return;
        }
        $items = DB::query('SELECT oi.*, p.title, p.type FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :id', ['id' => $order['id']])->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/order', [
            'order' => $order,
            'items' => $items,
            'title' => 'Sipariş #' . $order['order_no'],
        ]);
    }

    protected function authorized(array $order): bool
    {
        if (Auth::check() && (int) $order['user_id'] === Auth::id()) {
            return true;
        }
        $last = $_SESSION['checkout_response']['order_no'] ?? null;
        return $last === $order['order_no'];
    }
}
