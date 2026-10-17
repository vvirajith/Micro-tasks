<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Blocked = 'blocked';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Whether an account in this state is allowed to obtain a new access token.
     */
    public function canLogin(): bool
    {
        return match ($this) {
            self::Active, self::Pending => true,
            self::Suspended, self::Blocked => false,
        };
    }

    /**
     * Whether an already authenticated account may reach protected endpoints.
     *
     * Pending accounts keep access so they can verify their email and manage
     * their own profile; the `verified` middleware guards everything else.
     */
    public function canAccessProtectedRoutes(): bool
    {
        return match ($this) {
            self::Active, self::Pending => true,
            self::Suspended, self::Blocked => false,
        };
    }

    /**
     * Blocked accounts are terminal, so their tokens are purged on detection.
     */
    public function shouldRevokeTokens(): bool
    {
        return $this === self::Blocked;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending',
            self::Suspended => 'Suspended',
            self::Blocked => 'Blocked',
        };
    }

    /**
     * Message returned when the status denies access.
     */
    public function denialMessage(): string
    {
        return match ($this) {
            self::Suspended => 'Your account has been suspended. Please contact support.',
            self::Blocked => 'Your account has been blocked.',
            self::Active, self::Pending => '',
        };
    }
}
