@extends('adminlte::page')

@push('css')
    <style>
        /* Выравнивание виджета select2 под поля form-control Bootstrap 4 / AdminLTE. */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            height: calc(2.25rem + 2px);
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            outline: none;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem);
            color: #495057;
            padding-left: 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem);
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        /* Горизонтальный скролл карточки dl/dt/dd на show-страницах:
           длинные значения (адреса и т.п.) держатся одной строкой,
           при переполнении срабатывает overflow-x:auto на родителе. */
        .dl-horizontal-scroll { overflow-x: auto; }
        .dl-horizontal-scroll > .row { margin-left: 0; margin-right: 0; }
        .dl-horizontal-scroll dd { white-space: nowrap; }

        /* Многострочные значения dl (описания): перенос по словам
           с сохранением переносов строк из textarea. */
        .dl-horizontal-scroll dd.text-multiline {
            white-space: pre-wrap;
            overflow-wrap: break-word;
        }

        /* Сортировка: визуальные индикаторы */
        .sort-icon {
            opacity: 0.3;
            transition: opacity 0.2s;
            font-size: 0.8em;
        }
        a:hover .sort-icon {
            opacity: 0.6;
        }
        /* Активное поле: полная яркость */
        .sort-icon:not(:empty) {
            opacity: 1 !important;
            font-weight: bold;
        }
    </style>
@endpush

@push('js')
    @include('partials.toasts')
@endpush
