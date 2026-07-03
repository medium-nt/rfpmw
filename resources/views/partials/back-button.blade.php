@php
    $from = request('from');
    // Принимаем только внутренние пути вида "/...", без протокола/домена
    $target = ($from && str_starts_with($from, '/') && !str_starts_with($from, '//'))
        ? url($from)
        : $fallbackRoute;
@endphp
<a href="{{ $target }}" class="btn btn-default btn-sm mr-2">
    <i class="fas fa-arrow-left"></i> Назад
</a>
