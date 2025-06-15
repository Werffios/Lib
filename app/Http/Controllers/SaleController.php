<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Genera e imprime el comprobante de venta
     */
    public function printSale(Sale $sale)
    {
        // Cargamos las relaciones necesarias
        $sale->load(['customer', 'items.book', 'user']);

        return view('sales.print', [
            'sale' => $sale,
        ]);
    }
}
