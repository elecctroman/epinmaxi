<?php
namespace System\Controllers\Admin;

use System\Core\DB;
use System\Helpers\Flash;

class WalletController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $this->enforce('manage-wallets');
        $wallets = DB::query('SELECT w.*, u.email FROM wallets w INNER JOIN users u ON u.id = w.user_id ORDER BY w.created_at DESC')->fetchAll();
        $transactions = DB::query('SELECT wt.*, u.email FROM wallet_transactions wt INNER JOIN wallets w ON w.id = wt.wallet_id INNER JOIN users u ON u.id = w.user_id ORDER BY wt.created_at DESC LIMIT 50')->fetchAll();
        $this->render('wallets/index', [
            'title' => 'Cüzdanlar',
            'wallets' => $wallets,
            'transactions' => $transactions,
        ]);
    }

    public function adjust(int $id): void
    {
        $this->enforce('manage-wallets');
        $amount = (float) ($_POST['amount'] ?? 0);
        $note = trim($_POST['note'] ?? 'Manuel işlem');
        $type = $_POST['type'] ?? 'credit';
        if ($amount === 0.0) {
            Flash::set('Tutar gereklidir.', 'danger');
            $this->redirect('/admin/cuzdanlar');
        }
        DB::transaction(function () use ($id, $amount, $note, $type) {
            $factor = $type === 'debit' ? -1 : 1;
            DB::query('UPDATE wallets SET balance = balance + :amount WHERE id = :id', [
                'amount' => $amount * $factor,
                'id' => $id,
            ]);
            DB::query('INSERT INTO wallet_transactions (wallet_id, type, amount, note, created_at) VALUES (:wallet,:type,:amount,:note,NOW())', [
                'wallet' => $id,
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
            ]);
        });
        $this->audit('wallet-adjust', 'wallet', $id, ['amount' => $amount, 'type' => $type]);
        Flash::set('Cüzdan güncellendi.', 'success');
        $this->redirect('/admin/cuzdanlar');
    }
}
