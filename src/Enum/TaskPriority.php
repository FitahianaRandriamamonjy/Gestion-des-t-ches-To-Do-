<?php

namespace App\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Basse',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-success',
            self::MEDIUM => 'bg-warning text-dark',
            self::HIGH => 'bg-danger',
        };
    }
}
