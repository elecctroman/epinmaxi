<?php
namespace System\Controllers\Admin;

use System\Core\Auth;
use System\Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        if ((Auth::user()['role'] ?? null) !== 'admin') {
            http_response_code(403);
            echo 'Yetkiniz yok.';
            return;
        }

        $this->view('admin/dashboard', [], 'admin');
    }
}
