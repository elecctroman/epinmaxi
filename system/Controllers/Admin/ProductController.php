<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Core\Validator;
use System\Helpers\Flash;

class ProductController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-products');
        $query = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
        $params = [];
        if (!empty($_GET['q'])) {
            $query .= ' AND (p.title LIKE :q OR p.sku LIKE :q)';
            $params['q'] = '%' . $_GET['q'] . '%';
        }
        if (!empty($_GET['type'])) {
            $query .= ' AND p.type = :type';
            $params['type'] = $_GET['type'];
        }
        if (!empty($_GET['status'])) {
            $query .= ' AND p.status = :status';
            $params['status'] = $_GET['status'];
        }
        $query .= ' ORDER BY p.created_at DESC LIMIT 100';
        $products = DB::query($query, $params)->fetchAll();
        $categories = DB::query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

        $this->render('products/index', [
            'title' => 'Ürün Yönetimi',
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function create(): void
    {
        $this->enforce('manage-products');
        $categories = DB::query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
        $this->render('products/form', [
            'title' => 'Yeni Ürün',
            'categories' => $categories,
            'product' => null,
        ]);
    }

    public function store(): void
    {
        $this->enforce('manage-products');
        $data = $this->validateProduct();
        $gallery = $this->handleGalleryUpload();
        if (!empty($gallery)) {
            $data['gallery'] = $gallery;
        }
        if ($cover = $this->handleFileUpload($_FILES['cover_image'] ?? null)) {
            $data['cover_image'] = $cover;
        }
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_map(fn($key) => ':' . $key, array_keys($data)));
        DB::query("INSERT INTO products ({$columns}, created_at) VALUES ({$placeholders}, NOW())", $data);
        $productId = (int) DB::pdo()->lastInsertId();
        $this->audit('create', 'product', $productId, ['title' => $data['title']]);
        Flash::set('Ürün başarıyla oluşturuldu.', 'success');
        $this->redirect('/admin/urunler');
    }

    public function edit(int $id): void
    {
        $this->enforce('manage-products');
        $product = DB::query('SELECT * FROM products WHERE id = :id', ['id' => $id])->fetch();
        if (!$product) {
            Flash::set('Ürün bulunamadı.', 'danger');
            $this->redirect('/admin/urunler');
        }
        $categories = DB::query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
        $this->render('products/form', [
            'title' => 'Ürün Düzenle',
            'categories' => $categories,
            'product' => $product,
        ]);
    }

    public function update(int $id): void
    {
        $this->enforce('manage-products');
        $product = DB::query('SELECT * FROM products WHERE id = :id', ['id' => $id])->fetch();
        if (!$product) {
            Flash::set('Ürün bulunamadı.', 'danger');
            $this->redirect('/admin/urunler');
        }
        $data = $this->validateProduct($product);
        if ($cover = $this->handleFileUpload($_FILES['cover_image'] ?? null)) {
            $data['cover_image'] = $cover;
        }
        $gallery = $this->handleGalleryUpload($product['gallery'] ? json_decode($product['gallery'], true) : []);
        if (!empty($gallery)) {
            $data['gallery'] = $gallery;
        }
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }
        $data['id'] = $id;
        DB::query('UPDATE products SET ' . implode(',', $sets) . ' WHERE id = :id', $data);
        $this->audit('update', 'product', $id, ['title' => $data['title'] ?? $product['title']]);
        Flash::set('Ürün güncellendi.', 'success');
        $this->redirect('/admin/urunler');
    }

    public function destroy(int $id): void
    {
        $this->enforce('manage-products');
        DB::query('DELETE FROM products WHERE id = :id', ['id' => $id]);
        $this->audit('delete', 'product', $id);
        Flash::set('Ürün silindi.', 'success');
        $this->redirect('/admin/urunler');
    }

    protected function validateProduct(?array $existing = null): array
    {
        $required = Validator::required($_POST, [
            'title' => 'Başlık',
            'slug' => 'Slug',
            'sku' => 'SKU',
            'price' => 'Fiyat',
            'type' => 'Ürün tipi',
        ]);
        if ($required) {
            Flash::set(reset($required), 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/urunler');
        }
        $data = [
            'type' => $_POST['type'],
            'title' => trim($_POST['title']),
            'slug' => trim($_POST['slug']),
            'sku' => trim($_POST['sku']),
            'category_id' => $_POST['category_id'] ?: null,
            'price' => (float) $_POST['price'],
            'sale_price' => $_POST['sale_price'] !== '' ? (float) $_POST['sale_price'] : null,
            'currency' => $_POST['currency'] ?? 'TRY',
            'stock_policy' => $_POST['stock_policy'] ?? 'track_keys',
            'description' => sanitize_html($_POST['description'] ?? ''),
            'tags' => $this->encodeTags($_POST['tags'] ?? ''),
            'highlights' => $this->encodeHighlights($_POST['highlights'] ?? ''),
            'faq' => $this->encodeFaq($_POST['faq'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
        ];
        return $data;
    }

    protected function encodeTags(string $tags): ?string
    {
        $items = array_filter(array_map('trim', preg_split('/[,\n]+/', $tags)));
        return $items ? json_encode(array_values($items), JSON_UNESCAPED_UNICODE) : null;
    }

    protected function encodeHighlights(string $value): ?string
    {
        $items = array_filter(array_map('trim', preg_split('/\r?\n/', $value)));
        return $items ? json_encode(array_values($items), JSON_UNESCAPED_UNICODE) : null;
    }

    protected function encodeFaq(string $value): ?string
    {
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $value)));
        $items = [];
        foreach ($lines as $line) {
            [$question, $answer] = array_pad(explode('|', $line, 2), 2, '');
            if ($question && $answer) {
                $items[] = ['question' => trim($question), 'answer' => trim($answer)];
            }
        }
        return $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;
    }

    protected function handleFileUpload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'])) {
            Flash::set('Yalnızca PNG, JPG veya WEBP dosyaları kabul edilir.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/urunler');
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Flash::set('Görsel boyutu 2MB sınırını aşamaz.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/urunler');
        }
        $name = 'media_' . bin2hex(random_bytes(8)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $path = __DIR__ . '/../../../public/uploads/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            Flash::set('Dosya yüklenemedi.', 'danger');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/urunler');
        }
        return '/uploads/' . $name;
    }

    protected function handleGalleryUpload(array $existing = []): ?string
    {
        if (empty($_FILES['gallery']) || !is_array($_FILES['gallery']['name'])) {
            return $existing ? json_encode($existing, JSON_UNESCAPED_SLASHES) : null;
        }
        $files = $_FILES['gallery'];
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $uploaded = $this->handleFileUpload($file);
            if ($uploaded) {
                $existing[] = $uploaded;
            }
        }
        return $existing ? json_encode(array_values(array_unique($existing)), JSON_UNESCAPED_SLASHES) : null;
    }
}
