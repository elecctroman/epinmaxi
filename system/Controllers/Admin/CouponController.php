<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Core\Validator;
use System\Helpers\Flash;

class CouponController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-coupons');
        $coupons = DB::query('SELECT * FROM coupons ORDER BY created_at DESC LIMIT 100')->fetchAll();
        $this->render('coupons/index', [
            'title' => 'Kuponlar',
            'coupons' => $coupons,
        ]);
    }

    public function store(): void
    {
        $this->enforce('manage-coupons');
        $errors = Validator::required($_POST, [
            'code' => 'Kupon kodu',
            'type' => 'Kupon tipi',
            'value' => 'Tutar',
        ]);
        if ($errors) {
            Flash::set(reset($errors), 'danger');
            $this->redirect('/admin/kuponlar');
        }
        DB::query('INSERT INTO coupons (code,type,value,max_uses,used_count,min_subtotal,max_discount,per_user_limit,starts_at,ends_at,status) VALUES (:code,:type,:value,:max_uses,0,:min_subtotal,:max_discount,:per_user_limit,:starts_at,:ends_at,:status)', [
            'code' => strtoupper(trim($_POST['code'])),
            'type' => $_POST['type'],
            'value' => (float) $_POST['value'],
            'max_uses' => (int) ($_POST['max_uses'] ?? 0),
            'min_subtotal' => (float) ($_POST['min_subtotal'] ?? 0),
            'max_discount' => (float) ($_POST['max_discount'] ?? 0),
            'per_user_limit' => (int) ($_POST['per_user_limit'] ?? 0),
            'starts_at' => $_POST['starts_at'] ?: null,
            'ends_at' => $_POST['ends_at'] ?: null,
            'status' => $_POST['status'] ?? 'active',
        ]);
        $this->audit('create', 'coupon', (int) DB::pdo()->lastInsertId());
        Flash::set('Kupon oluşturuldu.', 'success');
        $this->redirect('/admin/kuponlar');
    }

    public function update(int $id): void
    {
        $this->enforce('manage-coupons');
        DB::query('UPDATE coupons SET type=:type,value=:value,max_uses=:max_uses,min_subtotal=:min_subtotal,max_discount=:max_discount,per_user_limit=:per_user_limit,starts_at=:starts_at,ends_at=:ends_at,status=:status WHERE id=:id', [
            'type' => $_POST['type'] ?? 'fixed',
            'value' => (float) ($_POST['value'] ?? 0),
            'max_uses' => (int) ($_POST['max_uses'] ?? 0),
            'min_subtotal' => (float) ($_POST['min_subtotal'] ?? 0),
            'max_discount' => (float) ($_POST['max_discount'] ?? 0),
            'per_user_limit' => (int) ($_POST['per_user_limit'] ?? 0),
            'starts_at' => $_POST['starts_at'] ?: null,
            'ends_at' => $_POST['ends_at'] ?: null,
            'status' => $_POST['status'] ?? 'active',
            'id' => $id,
        ]);
        $this->audit('update', 'coupon', $id);
        Flash::set('Kupon güncellendi.', 'success');
        $this->redirect('/admin/kuponlar');
    }

    public function destroy(int $id): void
    {
        $this->enforce('manage-coupons');
        DB::query('DELETE FROM coupons WHERE id = :id', ['id' => $id]);
        $this->audit('delete', 'coupon', $id);
        Flash::set('Kupon silindi.', 'success');
        $this->redirect('/admin/kuponlar');
    }
}
