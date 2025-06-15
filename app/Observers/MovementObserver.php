<?php

namespace App\Observers;

use App\Models\Movement;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class MovementObserver
{
    /**
     * Handle the Movement "created" event.
     */
    public function created(Movement $movement): void
    {
        // Solo procesamos movimientos directos (no los generados por ventas)
        if ($movement->reference_type === 'App\Models\Sale') {
            return;
        }

        $book = Book::find($movement->book_id);

        if (!$book) return;

        // Actualizar el stock según el tipo de movimiento
        switch ($movement->movement_type) {
            case 'entrada':
                $book->stock += $movement->quantity;
                break;
            case 'salida':
                $book->stock -= $movement->quantity;
                // Evitar stock negativo
                if ($book->stock < 0) $book->stock = 0;
                break;
            case 'ajuste':
                // El ajuste es directo, la cantidad ya representa el valor final
                $book->stock = $movement->quantity;
                break;
            case 'devolucion':
                $book->stock += $movement->quantity;
                break;
        }

        $book->save();
    }

    /**
     * Handle the Movement "updated" event.
     */
    public function updated(Movement $movement): void
    {
        // Si el movimiento se actualiza, recalculamos el stock
        $this->recalculateStock($movement->book_id);
    }

    /**
     * Handle the Movement "deleted" event.
     */
    public function deleted(Movement $movement): void
    {
        // Si un movimiento se elimina, recalculamos el stock
        $this->recalculateStock($movement->book_id);
    }

    /**
     * Recalcular el stock completo de un libro basado en sus movimientos
     */
    private function recalculateStock($bookId): void
    {
        $book = Book::find($bookId);

        if (!$book) return;

        // Establecer stock en 0 y recalcular desde los movimientos
        $book->stock = 0;

        $movements = Movement::where('book_id', $bookId)->orderBy('created_at')->get();

        foreach ($movements as $movement) {
            switch ($movement->movement_type) {
                case 'entrada':
                    $book->stock += $movement->quantity;
                    break;
                case 'salida':
                    $book->stock -= $movement->quantity;
                    if ($book->stock < 0) $book->stock = 0;
                    break;
                case 'ajuste':
                    $book->stock = $movement->quantity;
                    break;
                case 'devolucion':
                    $book->stock += $movement->quantity;
                    break;
            }
        }

        $book->save();
    }
}
