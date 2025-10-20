<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;
use System\Services\Admin\InventoryImportService;
use System\Services\Admin\AuditLogger;
use System\Core\Crypto;

class AccountPoolController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-accounts');
        $filters = [
            'product_id' => $_GET['product_id'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];
        $query = 'SELECT a.id, a.status, a.created_at, p.title, p.sku, a.order_item_id FROM product_accounts a INNER JOIN products p ON p.id = a.product_id WHERE 1=1';
        $params = [];
        if (!empty($filters['product_id'])) {
            $query .= ' AND a.product_id = :product';
            $params['product'] = $filters['product_id'];
        }
        if (!empty($filters['status'])) {
            $query .= ' AND a.status = :status';
            $params['status'] = $filters['status'];
        }
        $query .= ' ORDER BY a.created_at DESC LIMIT 200';
        $accounts = DB::query($query, $params)->fetchAll();
        $products = DB::query("SELECT id, title FROM products WHERE type = 'account' ORDER BY title")->fetchAll();

        $this->render('inventory/accounts', [
            'title' => 'Dijital Hesap Havuzu',
            'accounts' => $accounts,
            'products' => $products,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): void
    {
        $this->enforce('manage-accounts');
        $encryptionKey = setting('app.encryption_key');
        $account = DB::query('SELECT a.*, p.title FROM product_accounts a INNER JOIN products p ON p.id = a.product_id WHERE a.id = :id', ['id' => $id])->fetch();
        if (!$account || !$encryptionKey) {
            http_response_code(404);
            exit('Hesap bulunamadı.');
        }
        $payload = [
            'username' => Crypto::decrypt($account['username'], $encryptionKey),
            'password' => Crypto::decrypt($account['password_encrypted'], $encryptionKey),
            'meta' => $account['meta_json'] ? json_decode($account['meta_json'], true) : null,
        ];
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function import(): void
    {
        $this->enforce('manage-accounts');
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId) {
            Flash::set('Lütfen hedef ürünü seçin.', 'danger');
            $this->redirect('/admin/hesap-havuzu');
        }
        $encryptionKey = setting('app.encryption_key');
        if (!$encryptionKey) {
            Flash::set('Şifreleme anahtarı tanımlı değil.', 'danger');
            $this->redirect('/admin/hesap-havuzu');
        }
        try {
            $rows = InventoryImportService::readUpload($_FILES['import_file'] ?? []);
            $inserted = InventoryImportService::importAccounts($productId, $rows, $encryptionKey);
            AuditLogger::log('import', 'product_accounts', $productId, ['count' => $inserted]);
            Flash::set($inserted . ' adet hesap içe aktarıldı.', 'success');
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
        }
        $this->redirect('/admin/hesap-havuzu');
    }

    public function export(): void
    {
        $this->enforce('manage-accounts');
        $productId = (int) ($_GET['product_id'] ?? 0);
        $status = $_GET['status'] ?? 'unused';
        $params = ['status' => $status];
        $sql = 'SELECT a.id, p.title, p.sku, a.status, a.created_at FROM product_accounts a INNER JOIN products p ON p.id = a.product_id WHERE a.status = :status';
        if ($productId) {
            $sql .= ' AND a.product_id = :product';
            $params['product'] = $productId;
        }
        $rows = DB::query($sql, $params)->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="accounts_export.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Ürün', 'SKU', 'Durum', 'Oluşturulma']);
        foreach ($rows as $row) {
            fputcsv($output, [$row['id'], $row['title'], $row['sku'], $row['status'], $row['created_at']]);
        }
        fclose($output);
        exit;
    }

    public function destroy(int $id): void
    {
        $this->enforce('manage-accounts');
        DB::query('DELETE FROM product_accounts WHERE id = :id AND status != "used"', ['id' => $id]);
        $this->audit('delete', 'product_account', $id);
        Flash::set('Hesap kaydı silindi.', 'success');
        $this->redirect('/admin/hesap-havuzu');
    }
}
