<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para impresión de comprobantes de venta
Route::get('/sales/{sale}/print', [SaleController::class, 'printSale'])->name('sales.print');
