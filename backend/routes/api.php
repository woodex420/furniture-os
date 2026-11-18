<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LeadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->group(function () {
    // Products Module
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);

    // Inventory Module
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory', [InventoryController::class, 'update']);

    // Leads Module (Livewire Integration)
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Web Routes (Livewire/Frontend Shell)
|--------------------------------------------------------------------------
*/

Route::view('/', 'livewire.workspace.leads-index');

// CORS Configuration for React Frontend
header('Access-Control-Allow-Origin: https://woodex.com.pk');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
