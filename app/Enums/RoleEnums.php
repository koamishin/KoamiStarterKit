<?php

namespace App\Enums;

enum RoleEnums: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::USER => 'User',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
