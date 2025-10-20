<?php
namespace System\Services;

use System\Core\Crypto;
use System\Core\DB;

class LicenseKeyService
{
    protected string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function assignKeys(int $productId, int $orderItemId, int $quantity): array
    {
        return DB::transaction(function ($pdo) use ($productId, $orderItemId, $quantity) {
            $stmt = $pdo->prepare('SELECT id, code FROM product_keys WHERE product_id = :pid AND status = "unused" LIMIT :qty');
            $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
            $stmt->bindValue(':qty', $quantity, \PDO::PARAM_INT);
            $stmt->execute();
            $keys = $stmt->fetchAll();
            $ids = array_column($keys, 'id');
            if (count($ids) < $quantity) {
                throw new \RuntimeException('Yeterli E-PIN stoku bulunmuyor.');
            }
            $update = $pdo->prepare('UPDATE product_keys SET status="used", order_item_id=:orderId, used_at=NOW() WHERE id=:id');
            foreach ($ids as $id) {
                $update->execute(['orderId' => $orderItemId, 'id' => $id]);
            }
            return array_map(function ($row) {
                return base64_encode($row['code']);
            }, $keys);
        });
    }
}
