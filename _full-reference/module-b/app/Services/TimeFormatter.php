<?php

namespace App\Services;

/**
 * Форматирование времени приготовления в человекочитаемый вид.
 *
 * Примеры: 120 → «2 часа», 80 → «1 час 20 минут», 25 → «25 минут».
 * Падежи не учитываются согласно заданию.
 */
class TimeFormatter
{
    /**
     * Преобразует количество минут в строку вида «1 час 20 минут».
     */
    public function humanize(int|string|null $minutes): string
    {
        $minutes = (int) $minutes;

        if ($minutes <= 0) {
            return '0 минут';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours === 1 ? 'час' : 'часа');
        }

        if ($remaining > 0 || $hours === 0) {
            $parts[] = $remaining.' '.($remaining === 1 ? 'минута' : 'минут');
        }

        return implode(' ', $parts);
    }
}
