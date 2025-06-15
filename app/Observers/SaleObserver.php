<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Movement;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        Log::info('Venta creada: ' . $sale->id . ' - ' . $sale->invoice_number);

        // Solo procesamos ventas completadas
        if ($sale->status !== 'completed') {
            Log::info('Venta no completada, no se actualiza stock.');
            return;
        }

        // Cargamos los items de la venta si no están cargados
        if (!$sale->relationLoaded('items')) {
            $sale->load('items');
        }

        // Registrar los movimientos de salida para cada libro vendido
        foreach ($sale->items as $item) {
            Log::info('Procesando item: Libro #' . $item->book_id . ', Cantidad: ' . $item->quantity);

            $book = Book::find($item->book_id);

            if ($book) {
                // Crear movimiento de salida - usamos 'out' en lugar de 'salida'
                Movement::create([
                    'book_id' => $item->book_id,
                    'movement_type' => 'out', // Valor en inglés para la BD
                    'quantity' => $item->quantity,
                    'reference_type' => 'App\Models\Sale',
                    'reference_id' => $sale->id,
                    'user_id' => Auth::id() ?? 1,
                    'notes' => 'Venta: ' . $sale->invoice_number,
                ]);

                // Actualizar stock
                $book->stock -= $item->quantity;
                $book->save();

                Log::info('Stock actualizado para libro #' . $book->id . '. Nuevo stock: ' . $book->stock);
            } else {
                Log::warning('No se encontró el libro #' . $item->book_id);
            }
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Si la venta se canceló, debemos restaurar el stock
        if ($sale->status === 'cancelled' && $sale->getOriginal('status') !== 'cancelled') {
            Log::info('Venta cancelada: ' . $sale->id . ' - ' . $sale->invoice_number);

            // Cargamos los items si no están cargados
            if (!$sale->relationLoaded('items')) {
                $sale->load('items');
            }

            foreach ($sale->items as $item) {
                $book = Book::find($item->book_id);

                if ($book) {
                    // Crear movimiento de entrada (devolución) - usamos 'return' en lugar de 'devolucion'
                    Movement::create([
                        'book_id' => $item->book_id,
                        'movement_type' => 'return', // Valor en inglés para la BD
                        'quantity' => $item->quantity,
                        'reference_type' => 'App\Models\Sale',
                        'reference_id' => $sale->id,
                        'user_id' => Auth::id() ?? 1,
                        'notes' => 'Cancelación de venta: ' . $sale->invoice_number,
                    ]);

                    // Restaurar stock
                    $book->stock += $item->quantity;
                    $book->save();

                    Log::info('Stock restaurado para libro #' . $book->id . '. Nuevo stock: ' . $book->stock);
                }
            }
        }

        // Si la venta se marcó como devuelta, restauramos el stock
        if ($sale->status === 'returned' && $sale->getOriginal('status') !== 'returned') {
            Log::info('Venta devuelta: ' . $sale->id . ' - ' . $sale->invoice_number);

            // Cargamos los items si no están cargados
            if (!$sale->relationLoaded('items')) {
                $sale->load('items');
            }

            foreach ($sale->items as $item) {
                $book = Book::find($item->book_id);

                if ($book) {
                    // Crear movimiento de entrada (devolución) - usamos 'return' en lugar de 'devolucion'
                    Movement::create([
                        'book_id' => $item->book_id,
                        'movement_type' => 'return', // Valor en inglés para la BD
                        'quantity' => $item->quantity,
                        'reference_type' => 'App\Models\Sale',
                        'reference_id' => $sale->id,
                        'user_id' => Auth::id() ?? 1,
                        'notes' => 'Devolución de venta: ' . $sale->invoice_number,
                    ]);

                    // Restaurar stock
                    $book->stock += $item->quantity;
                    $book->save();

                    Log::info('Stock restaurado para libro #' . $book->id . '. Nuevo stock: ' . $book->stock);
                }
            }
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        // Si se elimina una venta completada, restauramos el stock
        if ($sale->status === 'completed') {
            Log::info('Venta eliminada: ' . $sale->id . ' - ' . $sale->invoice_number);

            // Cargamos los items si no están cargados
            if (!$sale->relationLoaded('items')) {
                $sale->load('items');
            }

            foreach ($sale->items as $item) {
                $book = Book::find($item->book_id);

                if ($book) {
                    // Restaurar stock
                    $book->stock += $item->quantity;
                    $book->save();

                    // Crear movimiento de entrada (devolución por eliminación) - usamos 'adjustment' en lugar de 'ajuste'
                    Movement::create([
                        'book_id' => $item->book_id,
                        'movement_type' => 'adjustment', // Valor en inglés para la BD
                        'quantity' => $item->quantity,
                        'reference_type' => 'App\Models\Sale',
                        'reference_id' => $sale->id,
                        'user_id' => Auth::id() ?? 1,
                        'notes' => 'Eliminación de venta: ' . $sale->invoice_number,
                    ]);

                    Log::info('Stock restaurado para libro #' . $book->id . '. Nuevo stock: ' . $book->stock);
                }
            }
        }
    }
}
