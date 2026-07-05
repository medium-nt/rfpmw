<?php

use App\Http\Controllers\ContactPersonController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\EmployedPersonController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectItemController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalItemController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestItemController;
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

Route::middleware(['auth', 'can:is-admin'])
    ->name('items.')
    ->prefix('items')
    ->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/', [ItemController::class, 'store'])->name('store');
        Route::get('/{item}/edit', [ItemController::class, 'edit'])->name('edit');
        Route::put('/{item}', [ItemController::class, 'update'])->name('update');
        Route::get('/{item}', [ItemController::class, 'show'])->name('show');
        Route::delete('/{item}', [ItemController::class, 'destroy'])->name('destroy');
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
        Route::delete('/{contractor}', [ContractorController::class, 'destroy'])->middleware('can:is-admin')->name('destroy');
        Route::get('/trashed', [ContractorController::class, 'trashed'])->name('trashed');
        Route::post('/{id}/restore', [ContractorController::class, 'restore'])->name('restore');
        Route::get('/dadata/party', [ContractorController::class, 'findParty'])
            ->middleware('throttle:30,1')
            ->name('dadata.party');
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
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->middleware('can:is-admin')->name('destroy');
    });

Route::middleware(['auth'])
    ->name('project-items.')
    ->prefix('projects/{project}/items')
    ->group(function () {
        Route::post('/', [ProjectItemController::class, 'store'])->name('store');
        Route::get('/{project_item}/edit', [ProjectItemController::class, 'edit'])->name('edit');
        Route::put('/{project_item}', [ProjectItemController::class, 'update'])->name('update');
        Route::delete('/{project_item}', [ProjectItemController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('requests.')
    ->group(function () {
        Route::get('contractors/{contractor}/requests/create', [RequestController::class, 'create'])->name('create');
        Route::post('contractors/{contractor}/requests', [RequestController::class, 'store'])->name('store');
        Route::get('requests', [RequestController::class, 'index'])->name('index');
        Route::get('requests/{request}', [RequestController::class, 'show'])->name('show');
        Route::get('requests/{request}/edit', [RequestController::class, 'edit'])->name('edit');
        Route::put('requests/{request}', [RequestController::class, 'update'])->name('update');
        Route::delete('requests/{request}', [RequestController::class, 'destroy'])->middleware('can:is-admin')->name('destroy');
    });

Route::middleware(['auth'])
    ->name('request-items.')
    ->prefix('requests/{request}/items')
    ->group(function () {
        Route::post('/', [RequestItemController::class, 'store'])->name('store');
        Route::get('/{request_item}/edit', [RequestItemController::class, 'edit'])->name('edit');
        Route::put('/{request_item}', [RequestItemController::class, 'update'])->name('update');
        Route::delete('/{request_item}', [RequestItemController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('proposals.')
    ->group(function () {
        Route::get('contractors/{contractor}/proposals/create', [ProposalController::class, 'create'])->name('create');
        Route::post('contractors/{contractor}/proposals', [ProposalController::class, 'store'])->name('store');
        Route::get('proposals', [ProposalController::class, 'index'])->name('index');
        Route::get('proposals/{proposal}', [ProposalController::class, 'show'])->name('show');
        Route::get('proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('edit');
        Route::put('proposals/{proposal}', [ProposalController::class, 'update'])->name('update');
        Route::delete('proposals/{proposal}', [ProposalController::class, 'destroy'])->middleware('can:is-admin')->name('destroy');
    });

Route::middleware(['auth'])
    ->name('proposal-items.')
    ->prefix('proposals/{proposal}/items')
    ->group(function () {
        Route::post('/', [ProposalItemController::class, 'store'])->name('store');
        Route::get('/{proposal_item}/edit', [ProposalItemController::class, 'edit'])->name('edit');
        Route::put('/{proposal_item}', [ProposalItemController::class, 'update'])->name('update');
        Route::delete('/{proposal_item}', [ProposalItemController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth'])
    ->name('events.')
    ->group(function () {
        Route::get('contractors/{contractor}/events/create', [EventController::class, 'create'])->name('create');
        Route::post('contractors/{contractor}/events', [EventController::class, 'store'])->name('store');
        Route::get('events', [EventController::class, 'index'])->name('index');
        Route::get('events/{event}', [EventController::class, 'show'])->name('show');
        Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('events/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->middleware('can:is-admin')->name('destroy');
    });

Route::middleware(['auth'])
    ->name('profile.')
    ->prefix('profile')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });
