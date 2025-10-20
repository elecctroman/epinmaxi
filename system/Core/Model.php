<?php
namespace System\Core;

use System\Core\DB;

abstract class Model
{
    protected string $table;

    public function find(int $id): ?array
    {
        $stmt = DB::query("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
