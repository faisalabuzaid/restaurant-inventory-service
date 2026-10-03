<?php

use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless JSON routes under /api. Both the web UI and the POS integration
| use these same endpoints; there is intentionally no second, UI-only API.
|
*/

Route::get('/ingredients', [IngredientController::class, 'index']);
Route::post('/ingredients', [IngredientController::class, 'store']);

Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);

Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::post('/menu-items', [MenuItemController::class, 'store']);
Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
Route::put('/menu-items/{menuItem}/recipe', [MenuItemController::class, 'updateRecipe']);

Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
Route::post('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send']);
