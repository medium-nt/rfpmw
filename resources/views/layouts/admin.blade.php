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
    </style>
@endpush

@push('js')
    @include('partials.toasts')
@endpush
