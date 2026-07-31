@props(['value' => null, 'limit' => 100])

@php
    /**
     * Разворачиваемый/сворачиваемый многострочный текст.
     *
     * Обрезает значение до $limit символов (Str::limit добавляет «…»),
     * полный текст кладётся в data-full. Клик по «показать»/«свернуть»
     * переключает состояния (см. public/js/expandable-text.js).
     * Переносы строк сохраняются (white-space: pre-line на preview).
     */
    $text = trim((string) ($value ?? ''));
    $isLong = mb_strlen($text) > $limit;
    $truncated = $isLong ? \Illuminate\Support\Str::limit($text, $limit) : $text;
@endphp

<span class="expandable-text">
    @if ($text === '')
        <span class="text-muted">—</span>
    @elseif ($isLong)
        <span class="expandable-text__preview" data-full="{{ $text }}">{{ $truncated }}</span>
        <a href="#" class="expandable-text__toggle">показать</a>
    @else
        <span class="expandable-text__preview">{{ $text }}</span>
    @endif
</span>
