<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Workspace\CustomerDetailController;
use App\Http\Middleware\PrivateWorkspaceResponse;
use Illuminate\Support\Facades\Route;

// The public Furniture storefront is not implemented yet. Never present a framework welcome
// page or example products as a finished shop.
Route::get('/', fn () => response('Reimagin v1 storefront is not available yet.', 503))
    ->name('home');

Route::middleware([PrivateWorkspaceResponse::class, 'guest'])->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:workspace-login')->name('login.store');
});

Route::middleware([PrivateWorkspaceResponse::class, 'auth'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    // The shell is available to the initial owner; business modules remain pending.
    Route::get('/admin', fn () => view('workspace.dashboard'))
        ->middleware('can:workspace.view')
        ->name('admin');
    Route::get('/admin/leads', fn () => view('workspace.leads'))
        ->middleware(['can:workspace.view', 'can:leads.view'])
        ->name('admin.leads');
    Route::get('/admin/contacts', fn () => view('workspace.contacts'))
        ->middleware(['can:workspace.view', 'can:contacts.view'])
        ->name('admin.contacts');
    // Authorize before looking up a record, so denied users cannot distinguish IDs.
    Route::get('/admin/leads/{id}', [CustomerDetailController::class, 'lead'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:leads.view'])
        ->name('admin.leads.show');
    Route::get('/admin/contacts/{id}', [CustomerDetailController::class, 'contact'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:contacts.view'])
        ->name('admin.contacts.show');
});
