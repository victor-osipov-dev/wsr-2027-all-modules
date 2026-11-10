<?php

namespace Tests\Unit;

use App\Services\TimeFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Проверка форматирования времени приготовления.
 */
class TimeFormatterTest extends TestCase
{
    public function test_it_formats_minutes_into_human_readable_text(): void
    {
        $formatter = new TimeFormatter();

        $this->assertSame('2 часа', $formatter->humanize(120));
        $this->assertSame('1 час 20 минут', $formatter->humanize(80));
        $this->assertSame('25 минут', $formatter->humanize(25));
        $this->assertSame('1 час', $formatter->humanize(60));
        $this->assertSame('1 минута', $formatter->humanize(1));
    }
}
