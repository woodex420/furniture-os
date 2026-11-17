<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\OwnerPasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Workspace\BlogPostController;
use App\Http\Controllers\Workspace\CityPageController;
use App\Http\Controllers\Workspace\ContactCreationController;
use App\Http\Controllers\Workspace\CustomerDetailController;
use App\Http\Controllers\Workspace\LeadCreationController;
use App\Http\Controllers\Workspace\LeadEditController;
use App\Http\Controllers\Workspace\MediaLibraryController;
use App\Http\Controllers\Workspace\PublicHeroImageController;
use App\Http\Controllers\Workspace\ServiceCopyController;
use App\Http\Controllers\Workspace\StorefrontHomeController;
use App\Http\Middleware\PrivateWorkspaceResponse;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The Laravel public storefront is not implemented. Only a separately enabled synthetic
// admin preview may direct its root to the workspace; normal installations remain 503.
Route::get('/', function () {
    if (config('furniture.admin_preview_landing')) {
        return response('', 302, [
            'Location' => '/admin',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    return response('Reimagin v1 storefront is not available yet.', 503);
})->name('home');

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

// Narrow read-only service-copy bridge, independent of the homepage preview flag.
Route::get('/storefront-api/services/copy', [ServiceCopyController::class, 'publicContent'])
    ->withoutMiddleware([
        EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
        ShareErrorsFromSession::class, PreventRequestForgery::class,
    ])->name('storefront.services.copy');

// Published Furniture service areas only; draft data never appears in the public API.
Route::get('/storefront-api/cities', [CityPageController::class, 'publicIndex'])
    ->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('storefront.cities.index');
Route::get('/storefront-api/cities/{slug}', [CityPageController::class, 'publicShow'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('storefront.cities.show');

// Published Furniture blog articles only; draft contents are never public.
Route::get('/storefront-api/blog', [BlogPostController::class, 'publicIndex'])
    ->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('storefront.blog.index');
Route::get('/storefront-api/blog/{slug}', [BlogPostController::class, 'publicShow'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('storefront.blog.show');

// Only the currently selected public hero image is exposed, never the private library.
Route::get('/storefront-api/home/image/{id}', [PublicHeroImageController::class, 'show'])
    ->whereUuid('id')
    ->withoutMiddleware([
        EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
        ShareErrorsFromSession::class, PreventRequestForgery::class,
    ])->name('storefront.home.image');

Route::middleware([PrivateWorkspaceResponse::class, 'guest'])->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    // Synthetic preview diagnostic only: check that the browser returned this session,
    // never return the token or grant access. Default installations do not expose it.
    Route::get('/login/preview-session', function (Request $request) {
        abort_unless(config('furniture.admin_preview_landing'), 404);
        $candidate = $request->header('X-Preview-Session-Token');

        return response()->json(['available' => is_string($candidate) && hash_equals($request->session()->token(), $candidate)]);
    })->name('login.preview-session');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:workspace-login')->name('login.store');
});

Route::middleware([PrivateWorkspaceResponse::class, 'auth'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    // Owner-only account security; not an account recovery or staff-management flow.
    Route::get('/admin/account/password', [OwnerPasswordController::class, 'edit'])
        ->middleware('can:workspace.view')->name('admin.owner.password.edit');
    Route::post('/admin/account/password', [OwnerPasswordController::class, 'update'])
        ->middleware(['can:workspace.view', 'throttle:5,1'])->name('admin.owner.password.update');
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
    // Temporary owner-only copy editor for existing Reimagin service pages.
    Route::get('/admin/website/services', [ServiceCopyController::class, 'index'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.services');
    Route::get('/admin/website/services/{slug}', [ServiceCopyController::class, 'edit'])
        ->where('slug', '[a-z0-9-]+')->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.services.edit');
    Route::post('/admin/website/services/{slug}', [ServiceCopyController::class, 'update'])
        ->where('slug', '[a-z0-9-]+')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.services.update');
    Route::post('/admin/website/services/{slug}/restore', [ServiceCopyController::class, 'restore'])
        ->where('slug', '[a-z0-9-]+')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.services.restore');
    // City service areas are separate from physical showrooms. No real locations are seeded.
    Route::get('/admin/website/cities', [CityPageController::class, 'index'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.cities');
    Route::get('/admin/website/cities/create', [CityPageController::class, 'create'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.cities.create');
    Route::post('/admin/website/cities', [CityPageController::class, 'store'])
        ->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.cities.store');
    Route::get('/admin/website/cities/{slug}/edit', [CityPageController::class, 'edit'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.cities.edit');
    Route::post('/admin/website/cities/{slug}', [CityPageController::class, 'update'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.cities.update');
    Route::post('/admin/website/cities/{slug}/restore', [CityPageController::class, 'restore'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.cities.restore');
    // Furniture-owned plain-text blog preview; old demo data remains separate.
    Route::get('/admin/website/blog', [BlogPostController::class, 'index'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.blog');
    Route::get('/admin/website/blog/create', [BlogPostController::class, 'create'])
        ->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.blog.create');
    Route::post('/admin/website/blog', [BlogPostController::class, 'store'])
        ->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.blog.store');
    Route::get('/admin/website/blog/{slug}/edit', [BlogPostController::class, 'edit'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit'])->name('admin.website.blog.edit');
    Route::post('/admin/website/blog/{slug}', [BlogPostController::class, 'update'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.blog.update');
    Route::post('/admin/website/blog/{slug}/restore', [BlogPostController::class, 'restore'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->middleware(['can:workspace.view', 'can:storefront.edit', 'throttle:20,1'])->name('admin.website.blog.restore');
    // Private media library; the separate public route serves only the selected hero image.
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
    // Limited owner-only edit; authorize before looking up a record.
    Route::get('/admin/leads/{id}/edit', [LeadEditController::class, 'edit'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:leads.view', 'can:leads.edit'])
        ->name('admin.leads.edit');
    Route::post('/admin/leads/{id}', [LeadEditController::class, 'update'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:leads.view', 'can:leads.edit', 'throttle:20,1'])
        ->name('admin.leads.update');
    // Authorize before looking up a record, so denied users cannot distinguish IDs.
    Route::get('/admin/leads/{id}', [CustomerDetailController::class, 'lead'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:leads.view'])
        ->name('admin.leads.show');
    Route::get('/admin/contacts/{id}', [CustomerDetailController::class, 'contact'])
        ->whereNumber('id')->middleware(['can:workspace.view', 'can:contacts.view'])
        ->name('admin.contacts.show');
});
