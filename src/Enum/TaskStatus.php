<?php

namespace App\Enum;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::IN_PROGRESS => 'En cours',
            self::DONE => 'Terminée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TODO => 'bg-secondary',
            self::IN_PROGRESS => 'bg-primary',
            self::DONE => 'bg-success',
        };
    }
}
