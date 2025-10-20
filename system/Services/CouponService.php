<?php
namespace System\Services;

use DateTimeImmutable;
use PDO;
use System\Core\DB;

class CouponService
{
    public function validate(string $code, float $subtotal, ?int $userId = null): array
    {
        $coupon = DB::query('SELECT * FROM coupons WHERE code = :code AND status = "active" LIMIT 1', ['code' => strtoupper($code)])->fetch(PDO::FETCH_ASSOC);
        if (!$coupon) {
            throw new \RuntimeException('Kupon bulunamadı veya aktif değil.');
        }
        $now = new DateTimeImmutable('now');
        if ($coupon['starts_at'] && $now < new DateTimeImmutable($coupon['starts_at'])) {
            throw new \RuntimeException('Kupon henüz kullanılamaz.');
        }
        if ($coupon['ends_at'] && $now > new DateTimeImmutable($coupon['ends_at'])) {
            throw new \RuntimeException('Kupon süresi dolmuş.');
        }
        if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
            throw new \RuntimeException('Kupon kullanım limiti doldu.');
        }
        if ($subtotal < (float) $coupon['min_subtotal']) {
            throw new \RuntimeException('Kupon için minimum sepet tutarı karşılanmıyor.');
        }
        if ($userId && !empty($coupon['per_user_limit'])) {
            $count = DB::query('SELECT COUNT(*) FROM orders WHERE user_id = :uid AND coupon_code = :code', [
                'uid' => $userId,
                'code' => $coupon['code'],
            ])->fetchColumn();
            if ($count >= (int) $coupon['per_user_limit']) {
                throw new \RuntimeException('Kupon kişisel kullanım limitine ulaştı.');
            }
        }
        $discount = 0.0;
        if ($coupon['type'] === 'percent') {
            $discount = $subtotal * ((float) $coupon['value'] / 100);
            if (!empty($coupon['max_discount'])) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
        } else {
            $discount = (float) $coupon['value'];
        }
        return [
            'coupon' => $coupon,
            'discount' => min($discount, $subtotal),
        ];
    }
}
