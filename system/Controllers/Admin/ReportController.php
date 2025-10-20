<?php
namespace System\Controllers\Admin;

use System\Services\Admin\ReportService;

class ReportController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('view-reports');
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $data = ReportService::collect($from . ' 00:00:00', $to . ' 23:59:59');
        $this->render('reports/index', [
            'title' => 'Raporlar',
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ]);
    }
}
