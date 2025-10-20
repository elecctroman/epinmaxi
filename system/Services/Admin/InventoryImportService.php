<?php
namespace System\Services\Admin;

use RuntimeException;
use ZipArchive;
use System\Core\Crypto;
use System\Core\DB;

class InventoryImportService
{
    public static function readUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Dosya yüklenemedi.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Dosya boyutu 5MB sınırını aşıyor.');
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $tmp = $file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('Geçersiz dosya yüklemesi.');
        }
        return match ($ext) {
            'csv' => self::readCsv($tmp),
            'xlsx' => self::readXlsx($tmp),
            default => throw new RuntimeException('Desteklenmeyen dosya formatı. Lütfen CSV veya XLSX yükleyin.'),
        };
    }

    public static function importKeys(int $productId, array $rows, string $encryptionKey): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO product_keys (product_id, code, meta_json, status, created_at) VALUES (:product_id,:code,:meta,"unused",NOW())');
        $count = 0;
        foreach ($rows as $row) {
            $code = trim($row['code'] ?? ($row[0] ?? ''));
            if ($code === '') {
                continue;
            }
            $meta = $row['meta'] ?? ($row[1] ?? null);
            $stmt->execute([
                'product_id' => $productId,
                'code' => Crypto::encrypt($code, $encryptionKey),
                'meta' => $meta ? json_encode(['note' => $meta], JSON_UNESCAPED_UNICODE) : null,
            ]);
            $count++;
        }
        return $count;
    }

    public static function importAccounts(int $productId, array $rows, string $encryptionKey): int
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('INSERT INTO product_accounts (product_id, username, password_encrypted, meta_json, status, created_at) VALUES (:product_id,:username,:password,:meta,"unused",NOW())');
        $count = 0;
        foreach ($rows as $row) {
            $username = trim($row['username'] ?? ($row[0] ?? ''));
            $password = trim($row['password'] ?? ($row[1] ?? ''));
            if ($username === '' || $password === '') {
                continue;
            }
            $meta = $row['meta'] ?? ($row[2] ?? null);
            $stmt->execute([
                'product_id' => $productId,
                'username' => Crypto::encrypt($username, $encryptionKey),
                'password' => Crypto::encrypt($password, $encryptionKey),
                'meta' => $meta ? json_encode(['note' => $meta], JSON_UNESCAPED_UNICODE) : null,
            ]);
            $count++;
        }
        return $count;
    }

    protected static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException('CSV dosyası açılamadı.');
        }
        $rows = [];
        $headers = null;
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if ($headers === null) {
                if (count($data) === 1) {
                    // try comma separated
                    $headers = array_map('trim', str_getcsv($data[0]));
                } else {
                    $headers = array_map('trim', $data);
                }
                continue;
            }
            if (count(array_filter($data, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($data) === 1 && count($headers) > 1) {
                $data = str_getcsv($data[0]);
            }
            $row = [];
            foreach ($headers as $index => $key) {
                $rowKey = strtolower($key);
                $row[$rowKey] = $data[$index] ?? null;
            }
            $rows[] = $row;
            if (count($rows) >= 2000) {
                break;
            }
        }
        fclose($handle);
        return $rows;
    }

    protected static function readXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('XLSX içe aktarma için ZipArchive eklentisi gereklidir.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('XLSX dosyası açılamadı.');
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet.xml');
        }
        $sharedStrings = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sharedDoc = simplexml_load_string($sharedXml);
            if ($sharedDoc) {
                foreach ($sharedDoc->si as $index => $si) {
                    $text = '';
                    foreach ($si->t as $t) {
                        $text .= (string) $t;
                    }
                    if ($text === '') {
                        $text = (string) $si->t;
                    }
                    $sharedStrings[(int) $index] = $text;
                }
            }
        }
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Çalışma sayfası bulunamadı.');
        }
        $sheet = simplexml_load_string($sheetXml);
        $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        $headers = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $value = (string) $cell->v;
                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }
                $cells[] = $value;
            }
            if (empty($headers)) {
                $headers = array_map(fn($v) => strtolower(trim($v)), $cells);
                continue;
            }
            if (count(array_filter($cells, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $rowData = [];
            foreach ($headers as $index => $key) {
                $rowData[$key] = $cells[$index] ?? null;
            }
            $rows[] = $rowData;
            if (count($rows) >= 2000) {
                break;
            }
        }
        $zip->close();
        return $rows;
    }
}
