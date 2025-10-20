<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Core\Validator;
use System\Helpers\Flash;

class CategoryController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-categories');
        $categories = DB::query('SELECT * FROM categories ORDER BY parent_id IS NULL DESC, parent_id, sort, name')->fetchAll();
        $this->render('categories/index', [
            'title' => 'Kategoriler',
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        $this->enforce('manage-categories');
        $errors = Validator::required($_POST, [
            'name' => 'Kategori adı',
            'slug' => 'Slug',
        ]);
        if ($errors) {
            Flash::set(reset($errors), 'danger');
            $this->redirect('/admin/kategoriler');
        }
        DB::query('INSERT INTO categories (name, slug, parent_id, sort) VALUES (:name,:slug,:parent,:sort)', [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug']),
            'parent' => $_POST['parent_id'] ?: null,
            'sort' => (int) ($_POST['sort'] ?? 0),
        ]);
        $this->audit('create', 'category', (int) DB::pdo()->lastInsertId());
        Flash::set('Kategori eklendi.', 'success');
        $this->redirect('/admin/kategoriler');
    }

    public function update(int $id): void
    {
        $this->enforce('manage-categories');
        $errors = Validator::required($_POST, [
            'name' => 'Kategori adı',
            'slug' => 'Slug',
        ]);
        if ($errors) {
            Flash::set(reset($errors), 'danger');
            $this->redirect('/admin/kategoriler');
        }
        DB::query('UPDATE categories SET name=:name, slug=:slug, parent_id=:parent, sort=:sort WHERE id=:id', [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug']),
            'parent' => $_POST['parent_id'] ?: null,
            'sort' => (int) ($_POST['sort'] ?? 0),
            'id' => $id,
        ]);
        $this->audit('update', 'category', $id);
        Flash::set('Kategori güncellendi.', 'success');
        $this->redirect('/admin/kategoriler');
    }

    public function destroy(int $id): void
    {
        $this->enforce('manage-categories');
        DB::query('UPDATE categories SET parent_id = NULL WHERE parent_id = :id', ['id' => $id]);
        DB::query('DELETE FROM categories WHERE id = :id', ['id' => $id]);
        $this->audit('delete', 'category', $id);
        Flash::set('Kategori silindi.', 'success');
        $this->redirect('/admin/kategoriler');
    }
}
