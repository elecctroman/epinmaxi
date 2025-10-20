<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;
use System\Services\Admin\InventoryImportService;
use System\Services\Admin\AuditLogger;

class KeyPoolController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-keys');
        $filters = [
            'product_id' => $_GET['product_id'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];
        $query = 'SELECT k.id, k.status, k.created_at, p.title, p.sku FROM product_keys k INNER JOIN products p ON p.id = k.product_id WHERE 1=1';
        $params = [];
        if (!empty($filters['product_id'])) {
            $query .= ' AND k.product_id = :product';
            $params['product'] = $filters['product_id'];
        }
        if (!empty($filters['status'])) {
            $query .= ' AND k.status = :status';
            $params['status'] = $filters['status'];
        }
        $query .= ' ORDER BY k.created_at DESC LIMIT 200';
        $keys = DB::query($query, $params)->fetchAll();
        $products = DB::query("SELECT id, title FROM products WHERE type IN ('epin','license') ORDER BY title")->fetchAll();

        $this->render('inventory/keys', [
            'title' => 'E-PIN & Lisans Havuzu',
            'keys' => $keys,
            'products' => $products,
            'filters' => $filters,
        ]);
    }

    public function import(): void
    {
        $this->enforce('manage-keys');
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$productId) {
            Flash::set('Lütfen hedef ürünü seçin.', 'danger');
            $this->redirect('/admin/epin-havuzu');
        }
        $encryptionKey = setting('app.encryption_key');
        if (!$encryptionKey) {
            Flash::set('Şifreleme anahtarı tanımlı değil.', 'danger');
            $this->redirect('/admin/epin-havuzu');
        }
        try {
            $rows = InventoryImportService::readUpload($_FILES['import_file'] ?? []);
            $inserted = InventoryImportService::importKeys($productId, $rows, $encryptionKey);
            AuditLogger::log('import', 'product_keys', $productId, ['count' => $inserted]);
            Flash::set($inserted . ' adet anahtar içe aktarıldı.', 'success');
        } catch (\Throwable $e) {
            Flash::set($e->getMessage(), 'danger');
        }
        $this->redirect('/admin/epin-havuzu');
    }

    public function export(): void
    {
        $this->enforce('manage-keys');
        $productId = (int) ($_GET['product_id'] ?? 0);
        $status = $_GET['status'] ?? 'unused';
        $params = ['status' => $status];
        $sql = 'SELECT k.id, p.title, p.sku, k.status, k.created_at FROM product_keys k INNER JOIN products p ON p.id = k.product_id WHERE k.status = :status';
        if ($productId) {
            $sql .= ' AND k.product_id = :product';
            $params['product'] = $productId;
        }
        $rows = DB::query($sql, $params)->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="keys_export.csv"');
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
        $this->enforce('manage-keys');
        DB::query('DELETE FROM product_keys WHERE id = :id AND status != "used"', ['id' => $id]);
        $this->audit('delete', 'product_key', $id);
        Flash::set('Anahtar silindi.', 'success');
        $this->redirect('/admin/epin-havuzu');
    }
}
