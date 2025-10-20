<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;

class TicketController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-support');
        $tickets = DB::query('SELECT t.*, u.email FROM tickets t INNER JOIN users u ON u.id = t.user_id ORDER BY t.status != "closed", t.created_at DESC')->fetchAll();
        $this->render('tickets/index', [
            'title' => 'Destek Talepleri',
            'tickets' => $tickets,
        ]);
    }

    public function show(int $id): void
    {
        $this->enforce('manage-support');
        $ticket = DB::query('SELECT t.*, u.email FROM tickets t INNER JOIN users u ON u.id = t.user_id WHERE t.id = :id', ['id' => $id])->fetch();
        if (!$ticket) {
            Flash::set('Destek kaydı bulunamadı.', 'danger');
            $this->redirect('/admin/destek');
        }
        $messages = DB::query('SELECT tm.*, u.email FROM ticket_messages tm LEFT JOIN users u ON u.id = tm.user_id WHERE tm.ticket_id = :id ORDER BY tm.created_at ASC', ['id' => $id])->fetchAll();
        $this->render('tickets/show', [
            'title' => 'Destek Bileti',
            'ticket' => $ticket,
            'messages' => $messages,
        ]);
    }

    public function reply(int $id): void
    {
        $this->enforce('manage-support');
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            Flash::set('Mesaj gereklidir.', 'danger');
            $this->redirect('/admin/destek/' . $id);
        }
        DB::query('INSERT INTO ticket_messages (ticket_id, user_id, message, attachments_json, created_at) VALUES (:ticket,:user,:message,NULL,NOW())', [
            'ticket' => $id,
            'user' => $this->currentUserId(),
            'message' => sanitize_html($message),
        ]);
        DB::query('UPDATE tickets SET status = "answered" WHERE id = :id', ['id' => $id]);
        $this->audit('reply', 'ticket', $id);
        Flash::set('Yanıt gönderildi.', 'success');
        $this->redirect('/admin/destek/' . $id);
    }

    public function close(int $id): void
    {
        $this->enforce('manage-support');
        DB::query('UPDATE tickets SET status = "closed" WHERE id = :id', ['id' => $id]);
        $this->audit('close', 'ticket', $id);
        Flash::set('Destek talebi kapatıldı.', 'success');
        $this->redirect('/admin/destek/' . $id);
    }

    protected function currentUserId(): ?int
    {
        $user = \System\Core\Auth::user();
        return $user['id'] ?? null;
    }
}
