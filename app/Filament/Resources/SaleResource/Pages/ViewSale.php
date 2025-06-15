<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información de Venta')
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('Número de Factura/Ticket'),
                        TextEntry::make('sale_date')
                            ->label('Fecha y Hora')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'completed' => 'Completada',
                                'cancelled' => 'Cancelada',
                                'returned' => 'Devuelta',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'returned' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_method')
                            ->label('Método de Pago')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'cash' => 'Efectivo',
                                'credit_card' => 'Tarjeta de Crédito',
                                'debit_card' => 'Tarjeta de Débito',
                                'transfer' => 'Transferencia',
                                default => $state,
                            }),
                        TextEntry::make('customer.name')
                            ->label('Cliente')
                            ->default('Cliente Ocasional'),
                        TextEntry::make('user.name')
                            ->label('Vendedor'),
                        TextEntry::make('tax_amount')
                            ->label('IVA')
                            ->money('MXN'),
                        TextEntry::make('discount_amount')
                            ->label('Descuento')
                            ->money('MXN'),
                        TextEntry::make('total_amount')
                            ->label('Total')
                            ->money('MXN')
                            ->weight('bold'),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Detalle de Productos')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Libros')
                            ->schema([
                                TextEntry::make('book.title')
                                    ->label('Libro'),
                                TextEntry::make('book.ISBN')
                                    ->label('ISBN'),
                                TextEntry::make('quantity')
                                    ->label('Cantidad'),
                                TextEntry::make('price')
                                    ->label('Precio Unitario')
                                    ->money('MXN'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('MXN')
                                    ->weight('bold'),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status !== 'cancelled'),
            Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('sales.print', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'cancelled')
                ->action(function ($record) {
                    $record->update(['status' => 'cancelled']);
                }),
        ];
    }
}
