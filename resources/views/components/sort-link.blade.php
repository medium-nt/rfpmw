@props(['field', 'title', 'currentField', 'currentDirection'])

<?php
// Логика переключения направления:
// - Новое поле или текущее направление = desc → следующее направление = asc
// - Иначе (текущее поле, направление = asc) → следующее направление = desc
$isNewSort = $field !== $currentField;
$nextDirection = ($isNewSort || $currentDirection === 'desc') ? 'asc' : 'desc';

// Иконка только для активного поля
$icon = '';
if (!$isNewSort) {
    $icon = $currentDirection === 'asc' ? ' ↑' : ' ↓';
}

// Построение URL с сохранением поисковых параметров
$queryParams = array_filter([
    'q' => request('q'),
    'sort' => $field,
    'direction' => $nextDirection,
]);

$url = request()->url() . '?' . http_build_query($queryParams);
?>

<a href="{{ $url }}" class="text-decoration-none text-dark">
    {{ $title }}<span class="sort-icon">{{ $icon }}</span>
</a>
