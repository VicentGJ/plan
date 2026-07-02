<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\StateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::resource('projects', ProjectController::class)
        ->except(['create', 'edit']);

    Route::resource('projects.members', ProjectMemberController::class)
        ->except(['create', 'edit', 'show'])
        ->scoped();

    Route::resource('projects.states', StateController::class)
        ->except(['create', 'edit', 'show'])
        ->scoped();

    Route::post('projects/{project}/states/reorder', [StateController::class, 'reorder'])
        ->name('projects.states.reorder');

    Route::post('projects/{project}/states/{state}/default', [StateController::class, 'setDefault'])
        ->name('projects.states.default')
        ->scopeBindings();
});

require __DIR__.'/settings.php';
