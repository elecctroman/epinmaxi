<?php
namespace System\Controllers\Admin;

use System\Core\Auth;
use System\Core\Controller;
use System\Core\Gate;
use System\Services\Admin\AuditLogger;
use System\Helpers\Flash;

abstract class AdminController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            Flash::set('Yönetim paneline erişmek için giriş yapın.', 'warning');
            header('Location: /admin/giris');
            exit;
        }
        if (!Gate::allows('access-admin')) {
            http_response_code(403);
            exit('Yönetim paneline erişim izniniz yok.');
        }
    }

    protected function render(string $view, array $data = []): void
    {
        $data['authUser'] = Auth::user();
        $this->view('admin/' . ltrim($view, '/'), $data, 'admin');
    }

    protected function enforce(string $ability): void
    {
        $this->authorize($ability);
    }

    protected function audit(string $action, string $entity, ?int $entityId = null, array $details = []): void
    {
        AuditLogger::log($action, $entity, $entityId, $details);
    }
}
