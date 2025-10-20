<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use System\Core\DB;
use System\Services\CouponService;

class CouponServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::pdo()->exec('DELETE FROM coupons');
        DB::pdo()->exec('DELETE FROM orders');
    }

    public function testPercentCouponCalculatesDiscountWithCap(): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO coupons (code,type,value,max_uses,used_count,min_subtotal,max_discount,per_user_limit,starts_at,ends_at,status) VALUES (:code,:type,:value,:max,:used,:min,:max_discount,:limit,:start,:end,:status)');
        $stmt->execute([
            'code' => 'WELCOME',
            'type' => 'percent',
            'value' => 20,
            'max' => 10,
            'used' => 0,
            'min' => 100,
            'max_discount' => 80,
            'limit' => 2,
            'start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'status' => 'active',
        ]);
        $service = new CouponService();
        $result = $service->validate('welcome', 600, 1);
        $this->assertSame(80.0, $result['discount']);
        $this->assertSame('WELCOME', $result['coupon']['code']);
    }

    public function testFixedCouponRespectsPerUserLimit(): void
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO coupons (code,type,value,max_uses,used_count,min_subtotal,max_discount,per_user_limit,starts_at,ends_at,status) VALUES (:code,:type,:value,:max,:used,:min,:max_discount,:limit,:start,:end,:status)');
        $stmt->execute([
            'code' => 'FIXED',
            'type' => 'fixed',
            'value' => 50,
            'max' => 5,
            'used' => 0,
            'min' => 0,
            'max_discount' => 0,
            'limit' => 1,
            'start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'status' => 'active',
        ]);
        $pdo->prepare('INSERT INTO orders (order_no, user_id, email, subtotal, discount_total, tax_total, grand_total, currency, payment_method, payment_status, status, created_at, coupon_code, coupon_discount) VALUES (:order_no,:user_id,:email,100,0,0,100,"TRY","mock","paid","completed",NOW(),:coupon,50)')
            ->execute([
                'order_no' => 'ORD-1',
                'user_id' => 5,
                'email' => 'demo@example.com',
                'coupon' => 'FIXED',
            ]);
        $this->expectException(\RuntimeException::class);
        $service = new CouponService();
        $service->validate('FIXED', 120, 5);
    }
}
