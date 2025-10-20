<?php
namespace System\Controllers\Admin;

use System\Core\DB;

class LogController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('view-logs');
        $logs = DB::query('SELECT l.*, u.email FROM logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 200')->fetchAll();
        $this->render('logs/index', [
            'title' => 'Denetim Günlükleri',
            'logs' => $logs,
        ]);
    }

    public function download(): void
    {
        $this->enforce('view-logs');
        $logs = DB::query('SELECT l.*, u.email FROM logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC')->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_logs.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Kullanıcı', 'Eylem', 'Nesne', 'Nesne ID', 'IP', 'Tarih']);
        foreach ($logs as $log) {
            fputcsv($output, [$log['id'], $log['email'], $log['action'], $log['entity'], $log['entity_id'], $log['ip'], $log['created_at']]);
        }
        fclose($output);
        exit;
    }
}
