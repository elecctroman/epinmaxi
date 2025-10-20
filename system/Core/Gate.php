<?php
namespace System\Core;

class Gate
{
    protected static array $abilities = [
        'access-admin' => ['admin', 'staff'],
        'view-dashboard' => ['admin', 'staff'],
        'manage-products' => ['admin', 'staff'],
        'manage-categories' => ['admin', 'staff'],
        'manage-keys' => ['admin', 'staff'],
        'manage-accounts' => ['admin', 'staff'],
        'manage-orders' => ['admin', 'staff'],
        'manage-payments' => ['admin', 'staff'],
        'manage-users' => ['admin'],
        'manage-coupons' => ['admin', 'staff'],
        'manage-wallets' => ['admin'],
        'manage-support' => ['admin', 'staff'],
        'manage-settings' => ['admin'],
        'view-reports' => ['admin', 'staff'],
        'view-logs' => ['admin', 'staff'],
        'manage-ip-blocks' => ['admin'],
    ];

    public static function allows(string $ability): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }
        if ($user['role'] === 'admin') {
            return true;
        }
        $custom = self::customAbilities($user);
        if (in_array('*', $custom, true)) {
            return true;
        }
        if (in_array($ability, $custom, true)) {
            return true;
        }
        $roles = self::$abilities[$ability] ?? [];
        return in_array($user['role'], $roles, true);
    }

    public static function menu(): array
    {
        $items = [
            'dashboard' => 'view-dashboard',
            'products' => 'manage-products',
            'keys' => 'manage-keys',
            'accounts' => 'manage-accounts',
            'categories' => 'manage-categories',
            'orders' => 'manage-orders',
            'payments' => 'manage-payments',
            'users' => 'manage-users',
            'coupons' => 'manage-coupons',
            'wallets' => 'manage-wallets',
            'support' => 'manage-support',
            'settings' => 'manage-settings',
            'logs' => 'view-logs',
            'reports' => 'view-reports',
            'ipblocks' => 'manage-ip-blocks',
        ];
        $visible = [];
        foreach ($items as $key => $ability) {
            if (self::allows($ability)) {
                $visible[$key] = $ability;
            }
        }
        return $visible;
    }

    public static function abilities(): array
    {
        return array_keys(self::$abilities);
    }

    protected static function customAbilities(array $user): array
    {
        if (empty($user['permissions_json'])) {
            return [];
        }
        $decoded = json_decode($user['permissions_json'], true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_filter(array_map('strval', $decoded));
    }
}
