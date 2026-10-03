<?php

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

Route::get('/ping', fn () => response()->json(['pong' => true]));
