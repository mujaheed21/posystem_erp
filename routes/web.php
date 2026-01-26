<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Transactional and data-driven routes have been moved to api.php.
*/

Route::get('/', function () {
    return response()->json(['status' => 'ok', 'message' => 'Kano ERP Gateway']);
});