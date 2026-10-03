<?php

use Illuminate\Support\Facades\Route;

// Single-page UI shell. All data goes through /api (see routes/api.php).
Route::view('/', 'app')->name('app');
