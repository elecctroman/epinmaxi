<?php
namespace System\Helpers;

class Sanitizer
{
    protected const ALLOWED_TAGS = '<p><a><strong><em><ul><ol><li><br><span><div><img><h2><h3><h4><blockquote><code>'; 

    public static function stripDangerousTags(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);
        return preg_replace('#on[a-z]+\s*=#i', '', $clean) ?? '';
    }
}
