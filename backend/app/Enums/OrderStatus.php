<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando',
            self::Confirmed => 'Confirmado',
            self::Shipped => 'Enviado',
            self::Canceled => 'Cancelado',
        };
    }

    public function canBeCanceled(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
