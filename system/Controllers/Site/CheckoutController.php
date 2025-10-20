<?php
namespace System\Controllers\Site;

use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Core\Mailer;
use System\Helpers\Flash;
use System\Services\CartService;
use System\Services\CouponService;
use System\Services\Delivery\DigitalDeliveryService;
use System\Services\OrderService;
use System\Services\Payments\IyzicoGateway;
use System\Services\Payments\MockGateway;
use System\Services\Payments\PayTRGateway;
use System\Services\Payments\PaymentGatewayInterface;
use System\Services\Payments\StripeGateway;

class CheckoutController extends Controller
{
    protected CartService $cart;

    public function __construct()
    {
        $this->cart = new CartService();
    }

    public function index(): void
    {
        $summary = $this->cart->totals();
        $coupon = $_SESSION['cart_coupon'] ?? null;
        $discount = $coupon['discount'] ?? 0;
        $taxRate = (float) (setting('tax.rate', 0));
        $taxTotal = $summary['subtotal'] * $taxRate / 100;
        $grand = max($summary['subtotal'] + $taxTotal - $discount, 0);
        $this->view('site/checkout', [
            'cart' => $summary,
            'coupon' => $coupon,
            'discount' => $discount,
            'grand' => $grand,
            'tax' => $taxTotal,
            'title' => 'Ödeme',
        ]);
    }

    public function store(): void
    {
        try {
            $summary = $this->cart->totals();
            if (empty($summary['items'])) {
                throw new \RuntimeException('Sepetiniz boş.');
            }
            $coupon = $_SESSION['cart_coupon'] ?? null;
            if (isset($_POST['coupon_code']) && $_POST['coupon_code'] !== '' && !$coupon) {
                $service = new CouponService();
                $coupon = $service->validate($_POST['coupon_code'], $summary['subtotal'], Auth::id());
                $_SESSION['cart_coupon'] = $coupon;
            }
            $discount = $coupon['discount'] ?? 0;
            $subtotal = $summary['subtotal'];
            $taxRate = (float) (setting('tax.rate', 0));
            $taxTotal = $subtotal * $taxRate / 100;
            $grand = max($subtotal + $taxTotal - $discount, 0);
            $orderNo = 'ORD' . strtoupper(bin2hex(random_bytes(4)));
            $gatewayName = $_POST['gateway'] ?? setting('payment.default', 'mock');
            $gateway = $this->resolveGateway($gatewayName);
            $delivery = new DigitalDeliveryService(setting('app.encryption_key', 'demo-enc-key-32chars!!demo'));
            $service = new OrderService($gateway, $delivery);
            $customerEmail = $_POST['email'] ?? (Auth::user()['email'] ?? '');
            $order = $service->checkout([
                'order_no' => $orderNo,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'grand_total' => $grand,
                'currency' => $summary['currency'],
                'payment_method' => $gatewayName,
                'tax_total' => $taxTotal,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ], $this->transformItems($summary['items']), [
                'email' => $_POST['email'] ?? (Auth::user()['email'] ?? ''),
                'phone' => $_POST['phone'] ?? null,
                'name' => $_POST['name'] ?? (Auth::user()['name'] ?? ''),
            ], $coupon);
            $this->cart->clear();
            unset($_SESSION['cart_coupon']);
            $_SESSION['checkout_response'] = $order;
            if ($order['status'] === 'success') {
                Mailer::send($customerEmail, 'Siparişiniz hazır - ' . $order['order_no'], '<p>Merhaba,</p><p>Siparişiniz başarıyla alındı ve dijital teslimatlar hesabınıza tanımlandı.</p>');
                DB::query('INSERT INTO logs (user_id, action, entity, entity_id, ip, created_at) VALUES (:user,:action,:entity,:entity_id,:ip,NOW())', [
                    'user' => Auth::id(),
                    'action' => 'order.completed',
                    'entity' => $order['order_no'],
                    'entity_id' => null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            }
            $this->redirect('/siparis/' . $order['order_no']);
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
            $this->redirect('/odeme');
        }
    }

    protected function transformItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
            ];
        }, $items);
    }

    protected function resolveGateway(string $name): PaymentGatewayInterface
    {
        $payments = [
            'mock' => fn () => new MockGateway(),
            'paytr' => fn () => new PayTRGateway([
                'merchant_id' => setting('paytr.merchant_id'),
                'merchant_key' => setting('paytr.merchant_key'),
                'merchant_salt' => setting('paytr.merchant_salt'),
            ]),
            'iyzico' => fn () => new IyzicoGateway([
                'api_key' => setting('iyzico.api_key'),
                'secret_key' => setting('iyzico.secret_key'),
            ]),
            'stripe' => fn () => new StripeGateway([
                'secret_key' => setting('stripe.secret_key'),
                'publishable_key' => setting('stripe.publishable_key'),
                'webhook_secret' => setting('stripe.webhook_secret'),
            ]),
        ];
        return ($payments[$name] ?? $payments['mock'])();
    }
}
