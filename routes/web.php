<?php

use App\Http\Controllers\ContactPersonController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\EmployedPersonController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
    'confirm' => false,
]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'can:is-admin'])
    ->name('users.')
    ->prefix('users')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('contractors.')
    ->prefix('contractors')
    ->group(function () {
        Route::get('/', [ContractorController::class, 'index'])->name('index');
        Route::get('/create', [ContractorController::class, 'create'])->name('create');
        Route::post('/', [ContractorController::class, 'store'])->name('store');
        Route::get('/{contractor}/edit', [ContractorController::class, 'edit'])->name('edit');
        Route::put('/{contractor}', [ContractorController::class, 'update'])->name('update');
        Route::delete('/{contractor}', [ContractorController::class, 'destroy'])->name('destroy');
        Route::get('/trashed', [ContractorController::class, 'trashed'])->name('trashed');
        Route::post('/{id}/restore', [ContractorController::class, 'restore'])->name('restore');
        Route::get('/{contractor}', [ContractorController::class, 'show'])->name('show');
    });

Route::middleware(['auth'])
    ->name('employed-people.')
    ->prefix('contractors/{contractor}/employed-people')
    ->group(function () {
        Route::post('/', [EmployedPersonController::class, 'store'])->name('store');
        Route::put('/{employed_person}', [EmployedPersonController::class, 'update'])->name('update');
        Route::delete('/{employed_person}', [EmployedPersonController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('contact-people.')
    ->group(function () {
        Route::get('contact-people', [ContactPersonController::class, 'index'])->name('index');
        Route::get('contact-people/{person}', [ContactPersonController::class, 'show'])->name('show');
        Route::get('contact-people/{person}/edit', [ContactPersonController::class, 'edit'])->name('edit');
        Route::put('contact-people/{person}', [ContactPersonController::class, 'update'])->name('update');

        Route::get('contractors/{contractor}/contact-people/create', [ContactPersonController::class, 'create'])->name('create');
        Route::post('contractors/{contractor}/contact-people', [ContactPersonController::class, 'store'])->name('store');
    });

Route::middleware(['auth'])
    ->name('projects.')
    ->group(function () {
        Route::get('contractors/{contractor}/projects/create', [ProjectController::class, 'create'])->name('create');
        Route::post('contractors/{contractor}/projects', [ProjectController::class, 'store'])->name('store');
        Route::get('projects', [ProjectController::class, 'index'])->name('index');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('projects/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('profile.')
    ->prefix('profile')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });
