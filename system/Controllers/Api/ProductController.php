<?php
namespace System\Controllers\Api;

use PDO;
use System\Core\DB;
use System\Helpers\Response;

class ProductController extends ApiController
{
    public function index(): void
    {
        $this->authenticate();
        $type = $_GET['type'] ?? null;
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = 'WHERE status = "active"';
        $params = [];
        if ($type) {
            $where .= ' AND type = :type';
            $params['type'] = $type;
        }
        if ($search !== '') {
            $where .= ' AND (title LIKE :search OR sku LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = 'SELECT id, type, title, slug, sku, price, sale_price, currency, stock_policy, description, status, created_at FROM products ' . $where . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = 'SELECT COUNT(*) FROM products ' . $where;
        $countStmt = DB::pdo()->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        Response::json([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }
}
