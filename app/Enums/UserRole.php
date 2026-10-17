<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Worker = 'worker';
    case Client = 'client';
    case Admin = 'admin';

    /**
     * Roles that may be created through the public registration endpoints.
     *
     * @return array<int, self>
     */
    public static function publiclyRegisterable(): array
    {
        return [self::Worker, self::Client];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Worker => 'Worker',
            self::Client => 'Client',
            self::Admin => 'Admin',
        };
    }
}
