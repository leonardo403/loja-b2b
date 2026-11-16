<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
