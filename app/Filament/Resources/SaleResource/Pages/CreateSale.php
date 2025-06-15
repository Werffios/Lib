<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\SaleItem;
use App\Models\Book;
use App\Models\Movement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar automáticamente el usuario actual como vendedor
        $data['user_id'] = auth()->id();

        // Guardamos los items temporalmente para procesarlos después de crear la venta
        $this->items = $data['items'] ?? [];

        // Eliminamos los items del array de datos ya que se procesarán por separado
        unset($data['items']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Crear la venta
        $sale = static::getModel()::create($data);

        // Crear los items de la venta guardados temporalmente
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'], // Añadimos el precio unitario
                    'subtotal' => $item['subtotal'],
                ]);

                // Registramos manualmente el movimiento ya que el observador puede no estar funcionando
                if ($sale->status === 'completed') {
                    $this->processStockUpdate($sale, $item);
                }
            }
        }

        return $sale;
    }

    /**
     * Actualiza manualmente el stock y crea el movimiento de inventario
     */
    protected function processStockUpdate($sale, $item): void
    {
        // Registrar log para depuración
        Log::info('Procesando manualmente item en CreateSale: Libro #' . $item['book_id'] . ', Cantidad: ' . $item['quantity']);

        $book = Book::find($item['book_id']);

        if ($book) {
            // Crear movimiento de salida - usamos 'out' en lugar de 'salida'
            Movement::create([
                'book_id' => $item['book_id'],
                'movement_type' => 'out', // Valor en inglés para la BD
                'quantity' => $item['quantity'],
                'reference_type' => 'App\Models\Sale',
                'reference_id' => $sale->id,
                'user_id' => Auth::id() ?? 1,
                'notes' => 'Venta: ' . $sale->invoice_number,
            ]);

            // Actualizar stock
            $book->stock -= $item['quantity'];
            $book->save();

            Log::info('Stock actualizado manualmente para libro #' . $book->id . '. Nuevo stock: ' . $book->stock);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
