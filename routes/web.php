<?php

use App\Http\Controllers\KontakExportController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', Authenticate::class])->get('/admin/kontak/export', KontakExportController::class)
    ->name('kontaks.export');
