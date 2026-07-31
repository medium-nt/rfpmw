<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExpandableTextComponentTest extends TestCase
{
    #[Test]
    public function long_text_is_truncated_with_toggle_and_data_full(): void
    {
        // 150 символов - больше лимита по умолчанию (100)
        $longText = str_repeat('a', 150);

        $html = Blade::render(
            '<x-expandable-text :value="$v" />',
            ['v' => $longText]
        );

        // Проверяем наличие preview-класса
        $this->assertStringContainsString('expandable-text__preview', $html);

        // Проверяем наличие data-full с ПОЛНЫМ текстом (150 символов)
        $this->assertStringContainsString('data-full="'.$longText.'"', $html);

        // Проверяем наличие ссылки "показать"
        $this->assertStringContainsString('показать', $html);

        // Проверяем, что в HTML есть обрезанный текст (около 100 символов + '...')
        $this->assertStringContainsString('aaaa...', $html);

        // Проверяем, что полный текст НЕ виден в открытом виде (только в data-full)
        // Полный текст (150 символов) должен быть только в атрибуте, а не в тексте контента
        preg_match('/data-full="([^"]+)"/', $html, $matches);
        $this->assertCount(2, $matches);
        $this->assertEquals($longText, $matches[1]);

        // Проверяем, что текст внутри preview-тега обрезан (не равен полному)
        preg_match('/expandable-text__preview[^>]*>([^<]+)</', $html, $previewMatches);
        $previewText = trim($previewMatches[1]);
        $this->assertLessThan(110, mb_strlen($previewText));
        $this->assertGreaterThan(100, mb_strlen($previewText));
        $this->assertStringEndsWith('...', $previewText);
        $this->assertNotEquals($longText, $previewText);
    }

    #[Test]
    public function short_text_is_rendered_without_toggle(): void
    {
        $shortText = 'короткий текст';

        $html = Blade::render(
            '<x-expandable-text :value="$v" />',
            ['v' => $shortText]
        );

        // Проверяем наличие полного текста
        $this->assertStringContainsString($shortText, $html);

        // Проверяем отсутствие атрибута data-full
        $this->assertStringNotContainsString('data-full', $html);

        // Проверяем отсутствие текста "показать"
        $this->assertStringNotContainsString('показать', $html);
    }

    #[Test]
    public function empty_value_shows_dash(): void
    {
        // Тестируем null
        $htmlNull = Blade::render(
            '<x-expandable-text :value="$v" />',
            ['v' => null]
        );
        $this->assertStringContainsString('text-muted', $htmlNull);
        $this->assertStringContainsString('—', $htmlNull);
        $this->assertStringNotContainsString('показать', $htmlNull);

        // Тестируем пустую строку
        $htmlEmpty = Blade::render(
            '<x-expandable-text :value="$v" />',
            ['v' => '']
        );
        $this->assertStringContainsString('text-muted', $htmlEmpty);
        $this->assertStringContainsString('—', $htmlEmpty);
        $this->assertStringNotContainsString('показать', $htmlEmpty);

        // Тестируем строку из пробелов
        $htmlSpaces = Blade::render(
            '<x-expandable-text :value="$v" />',
            ['v' => '   ']
        );
        $this->assertStringContainsString('text-muted', $htmlSpaces);
        $this->assertStringContainsString('—', $htmlSpaces);
        $this->assertStringNotContainsString('показать', $htmlSpaces);
    }

    #[Test]
    public function custom_limit_prop(): void
    {
        // 10 символов - больше кастомного лимита (5)
        $text = str_repeat('a', 10);

        $html = Blade::render(
            '<x-expandable-text :value="$v" :limit="5" />',
            ['v' => $text]
        );

        // Проверяем наличие toggle при превышении кастомного лимита
        $this->assertStringContainsString('показать', $html);

        // Проверяем наличие data-full с полным текстом
        $this->assertStringContainsString('data-full="'.$text.'"', $html);

        // Проверяем, что текст внутри preview-тега обрезан до кастомного лимита
        preg_match('/expandable-text__preview[^>]*>([^<]+)</', $html, $previewMatches);
        $previewText = trim($previewMatches[1]);
        $this->assertLessThan(10, mb_strlen($previewText));
        $this->assertGreaterThan(5, mb_strlen($previewText));
        $this->assertStringEndsWith('...', $previewText);
        $this->assertNotEquals($text, $previewText);
    }
}
