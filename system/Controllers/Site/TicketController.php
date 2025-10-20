<?php
namespace System\Controllers\Site;

use PDO;
use System\Core\Auth;
use System\Core\Controller;
use System\Core\DB;
use System\Helpers\Flash;

class TicketController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $tickets = DB::query('SELECT * FROM tickets WHERE user_id = :uid ORDER BY created_at DESC', ['uid' => Auth::id()])->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/tickets/index', [
            'tickets' => $tickets,
            'title' => 'Destek Taleplerim',
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($subject === '' || $message === '') {
            Flash::set('Konu ve mesaj zorunludur.', 'danger');
            $this->redirect('/destek');
            return;
        }
        DB::transaction(function ($pdo) use ($subject, $message) {
            $stmt = $pdo->prepare('INSERT INTO tickets (user_id, subject, status, priority, created_at) VALUES (:uid,:subject,:status,:priority,NOW())');
            $stmt->execute([
                'uid' => Auth::id(),
                'subject' => $subject,
                'status' => 'open',
                'priority' => $_POST['priority'] ?? 'normal',
            ]);
            $ticketId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO ticket_messages (ticket_id, user_id, message, attachments_json, created_at) VALUES (:ticket,:user,:message,:attachments,NOW())')->execute([
                'ticket' => $ticketId,
                'user' => Auth::id(),
                'message' => $message,
                'attachments' => json_encode([]),
            ]);
        });
        Flash::set('Destek talebiniz oluşturuldu.', 'success');
        $this->redirect('/destek');
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        $ticket = DB::query('SELECT * FROM tickets WHERE id = :id AND user_id = :uid', ['id' => $id, 'uid' => Auth::id()])->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) {
            Flash::set('Talep bulunamadı.', 'danger');
            $this->redirect('/destek');
            return;
        }
        $messages = DB::query('SELECT tm.*, u.name FROM ticket_messages tm LEFT JOIN users u ON u.id = tm.user_id WHERE tm.ticket_id = :id ORDER BY tm.created_at ASC', ['id' => $ticket['id']])->fetchAll(PDO::FETCH_ASSOC);
        $this->view('site/tickets/show', [
            'ticket' => $ticket,
            'messages' => $messages,
            'title' => $ticket['subject'],
        ]);
    }

    public function reply(int $id): void
    {
        Auth::requireLogin();
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            Flash::set('Mesaj boş olamaz.', 'danger');
            $this->redirect('/destek/' . $id);
            return;
        }
        DB::query('INSERT INTO ticket_messages (ticket_id, user_id, message, attachments_json, created_at) VALUES (:ticket,:user,:message,:attachments,NOW())', [
            'ticket' => $id,
            'user' => Auth::id(),
            'message' => $message,
            'attachments' => json_encode([]),
        ]);
        Flash::set('Yanıtınız gönderildi.', 'success');
        $this->redirect('/destek/' . $id);
    }
}
