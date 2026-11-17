<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Workspace\ContactCreationController;
use App\Http\Controllers\Workspace\CustomerDetailController;
use App\Http\Controllers\Workspace\LeadCreationController;
use App\Http\Controllers\Workspace\MediaLibraryController;
use App\Http\Controllers\Workspace\PublicHeroImageController;
use App\Http\Controllers\Workspace\StorefrontHomeController;
use App\Http\Middleware\PrivateWorkspaceResponse;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The public Furniture storefront is not implemented yet. Never present a framework welcome
// page or example products as a finished shop.
Route::get('/', fn () => response('Reimagin v1 storefront is not available yet.', 503))
    ->name('home');

// Read-only public homepage content; writes are restricted to the owner workspace.
Route::get('/storefront-api/home', [StorefrontHomeController::class, 'publicContent'])
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ])
    ->name('storefront.home.content');

// Only the currently selected public hero image is exposed, never the private library.
Route::get('/storefront-api/home/image/{id}', [PublicHeroImageController::class, 'show'])
    ->whereUuid('id')
    ->withoutMiddleware([
        EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
        ShareErrorsFromSession::class, PreventRequestForgery::class,
    ])->name('storefront.home.image');

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
    // Small storefront integration, disabled by default until enabled for testing.
    Route::get('/admin/website/home', [StorefrontHomeController::class, 'edit'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.storefront.home');
    Route::post('/admin/website/home', [StorefrontHomeController::class, 'update'])
        ->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.storefront.home.update');
    Route::post('/admin/website/home/restore', [StorefrontHomeController::class, 'restore'])
        ->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.storefront.home.restore');
    // Private image experiment: files are not publicly served or connected to the storefront.
    Route::get('/admin/website/media', [MediaLibraryController::class, 'index'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.media');
    Route::post('/admin/website/media', [MediaLibraryController::class, 'store'])
        ->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.media.store');
    Route::get('/admin/website/media/{id}/file', [MediaLibraryController::class, 'show'])
        ->whereUuid('id')->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.media.file');
    // Experimental owner-only creation remains disabled unless explicitly enabled.
    Route::get('/admin/leads/create', [LeadCreationController::class, 'create'])
        ->middleware(['can:workspace.view', 'can:leads.view', 'can:leads.create'])
        ->name('admin.leads.create');
    Route::post('/admin/leads', [LeadCreationController::class, 'store'])
        ->middleware(['can:workspace.view', 'can:leads.view', 'can:leads.create', 'throttle:20,1'])
        ->name('admin.leads.store');
    Route::get('/admin/contacts/create', [ContactCreationController::class, 'create'])
        ->middleware(['can:workspace.view', 'can:contacts.view', 'can:contacts.create'])
        ->name('admin.contacts.create');
    Route::post('/admin/contacts', [ContactCreationController::class, 'store'])
        ->middleware(['can:workspace.view', 'can:contacts.view', 'can:contacts.create', 'throttle:20,1'])
        ->name('admin.contacts.store');
    // Authorize before looking up a record, so denied users cannot distinguish IDs.
    Route::get('/admin/leads/{id}', [CustomerDetailController::class, 'lead'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:leads.view'])
        ->name('admin.leads.show');
    Route::get('/admin/contacts/{id}', [CustomerDetailController::class, 'contact'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:contacts.view'])
        ->name('admin.contacts.show');
});
