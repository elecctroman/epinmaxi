<?php
namespace System\Services\Delivery;

use PDO;
use System\Core\Crypto;
use System\Core\DB;

class DigitalDeliveryService
{
    protected string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    public function reserveKeys(int $productId, int $orderItemId, int $quantity): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT id, code FROM product_keys WHERE product_id = :pid AND status = "unused" LIMIT :qty FOR UPDATE');
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
        $stmt->execute();
        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($keys) < $quantity) {
            throw new \RuntimeException('Yeterli ürün anahtarı bulunamadı.');
        }
        $update = $pdo->prepare('UPDATE product_keys SET status = "used", order_item_id = :orderId, used_at = NOW() WHERE id = :id');
        $delivered = [];
        foreach ($keys as $row) {
            $update->execute([
                'orderId' => $orderItemId,
                'id' => $row['id'],
            ]);
            $delivered[] = Crypto::decrypt($row['code'], $this->encryptionKey);
        }
        return $delivered;
    }

    public function reserveAccounts(int $productId, int $orderItemId, int $quantity): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT id, username, password_encrypted, meta_json FROM product_accounts WHERE product_id = :pid AND status = "unused" LIMIT :qty FOR UPDATE');
        $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
        $stmt->execute();
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($accounts) < $quantity) {
            throw new \RuntimeException('Yeterli hesap bulunamadı.');
        }
        $update = $pdo->prepare('UPDATE product_accounts SET status = "used", order_item_id = :orderId, used_at = NOW() WHERE id = :id');
        $delivered = [];
        foreach ($accounts as $row) {
            $update->execute([
                'orderId' => $orderItemId,
                'id' => $row['id'],
            ]);
            $delivered[] = [
                'username' => Crypto::decrypt($row['username'], $this->encryptionKey),
                'password' => Crypto::decrypt($row['password_encrypted'], $this->encryptionKey),
                'extra' => $row['meta_json'] ? json_decode($row['meta_json'], true) : null,
            ];
        }
        return $delivered;
    }
}
