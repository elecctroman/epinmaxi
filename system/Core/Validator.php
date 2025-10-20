<?php
namespace System\Core;

class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[$field] = $label . ' alanı gereklidir.';
            }
        }
        return $errors;
    }
}
