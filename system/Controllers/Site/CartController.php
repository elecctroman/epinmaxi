<?php
namespace System\Controllers\Site;

use System\Core\Controller;
use System\Helpers\Flash;
use System\Services\CartService;
use System\Services\CouponService;

class CartController extends Controller
{
    protected CartService $cart;

    public function __construct()
    {
        $this->cart = new CartService();
    }

    public function show(): void
    {
        $summary = $this->cart->totals();
        $this->view('site/cart', [
            'cart' => $summary,
            'title' => 'Sepet',
        ]);
    }

    public function add(): void
    {
        try {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $qty = (int) ($_POST['qty'] ?? 1);
            $this->cart->add($productId, max(1, $qty));
            if ($this->wantsJson()) {
                json_response(['message' => 'Sepete eklendi', 'cart' => $this->cart->totals()]);
                return;
            }
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/sepet');
        } catch (\Throwable $e) {
            if ($this->wantsJson()) {
                json_response(['error' => $e->getMessage()], 422);
                return;
            }
            Flash::set($e->getMessage(), 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/sepet');
        }
    }

    public function update(): void
    {
        $items = $_POST['items'] ?? [];
        foreach ($items as $item) {
            $this->cart->update((int) ($item['product_id'] ?? 0), (int) ($item['qty'] ?? 1));
        }
        Flash::set('Sepet güncellendi.', 'success');
        $this->redirect('/sepet');
    }

    public function remove(int $productId): void
    {
        $this->cart->remove($productId);
        Flash::set('Ürün sepetten çıkarıldı.', 'info');
        $this->redirect('/sepet');
    }

    public function applyCoupon(): void
    {
        try {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            if ($code === '') {
                throw new \RuntimeException('Kupon kodu boş olamaz.');
            }
            $totals = $this->cart->totals();
            $couponService = new CouponService();
            $coupon = $couponService->validate($code, $totals['subtotal'], \System\Core\Auth::id());
            $_SESSION['cart_coupon'] = $coupon;
            if ($this->wantsJson()) {
                json_response(['message' => 'Kupon uygulandı.', 'coupon' => $coupon]);
                return;
            }
            Flash::set('Kupon uygulandı.', 'success');
            $this->redirect('/sepet');
        } catch (\Throwable $e) {
            if ($this->wantsJson()) {
                json_response(['error' => $e->getMessage()], 422);
                return;
            }
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/sepet');
        }
    }
}
