<?php
namespace System\Helpers;

class Paginator
{
    public static function links(int $current, int $totalPages, string $baseUrl): string
    {
        $html = '<nav class="pagination" aria-label="Sayfalama">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i === $current ? ' aria-current="page" class="is-active"' : '';
            $html .= '<a href="' . htmlspecialchars($baseUrl) . '?page=' . $i . '"' . $active . '>' . $i . '</a>';
        }
        return $html . '</nav>';
    }
}
