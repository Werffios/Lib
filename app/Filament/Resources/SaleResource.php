<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Book;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $modelLabel = 'venta';
    protected static ?string $navigationLabel = 'ventas';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de Venta')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Número de Factura/Ticket')
                            ->default(fn () => 'FAC-' . Str::upper(Str::random(8)))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\DateTimePicker::make('sale_date')
                            ->label('Fecha y Hora de Venta')
                            ->required()
                            ->default(now()),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'completed' => 'Completada',
                                'cancelled' => 'Cancelada',
                                'returned' => 'Devuelta',
                            ])
                            ->default('completed')
                            ->required(),
                        Select::make('payment_method')
                            ->label('Método de Pago')
                            ->options([
                                'cash' => 'Efectivo',
                                'credit_card' => 'Tarjeta de Crédito',
                                'debit_card' => 'Tarjeta de Débito',
                                'transfer' => 'Transferencia',
                            ])
                            ->required(),
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono'),
                            ])
                            ->placeholder('Cliente Ocasional'),
                        TextInput::make('tax_amount')
                            ->label('IVA')
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('discount_amount')
                            ->label('Descuento')
                            ->numeric()
                            ->default(0),
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->readOnly(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Detalle de Productos')
                    ->schema([
                        Repeater::make('items')
                            ->label('Libros')
                            ->schema([
                                Select::make('book_id')
                                    ->label('Libro')
                                    ->options(function () {
                                        return Book::where('stock', '>', 0)
                                            ->get()
                                            ->pluck('title', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if (!$state) return;

                                        $book = Book::find($state);
                                        if ($book) {
                                            $set('price', $book->sale_price);
                                            $set('quantity', 1);
                                            $set('subtotal', $book->sale_price);

                                            // Recalcular el total
                                            $items = $get('../../items');
                                            $total = 0;

                                            foreach ($items as $item) {
                                                if (isset($item['subtotal'])) {
                                                    $total += $item['subtotal'];
                                                }
                                            }

                                            $discount = floatval($get('../../discount_amount') ?? 0);
                                            $set('../../total_amount', $total - $discount);
                                        }
                                    }),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $bookId = $get('book_id');
                                        if (!$bookId) return;

                                        $book = Book::find($bookId);
                                        if ($book) {
                                            // No permitir cantidades mayores que el stock disponible
                                            if ($state > $book->stock) {
                                                $state = $book->stock;
                                                $set('quantity', $book->stock);
                                            }

                                            $price = floatval($get('price'));
                                            $subtotal = $price * intval($state);
                                            $set('subtotal', $subtotal);

                                            // Recalcular el total
                                            $items = $get('../../items');
                                            $total = 0;

                                            foreach ($items as $item) {
                                                if (isset($item['subtotal'])) {
                                                    $total += $item['subtotal'];
                                                }
                                            }

                                            $discount = floatval($get('../../discount_amount') ?? 0);
                                            $set('../../total_amount', $total - $discount);
                                        }
                                    }),
                                TextInput::make('price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $quantity = intval($get('quantity'));
                                        $subtotal = floatval($state) * $quantity;
                                        $set('subtotal', $subtotal);

                                        // Recalcular el total
                                        $items = $get('../../items');
                                        $total = 0;

                                        foreach ($items as $item) {
                                            if (isset($item['subtotal'])) {
                                                $total += $item['subtotal'];
                                            }
                                        }

                                        $discount = floatval($get('../../discount_amount') ?? 0);
                                        $set('../../total_amount', $total - $discount);
                                    }),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->readOnly()
                                    ->required(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Factura/Ticket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->default('Cliente Ocasional'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        'returned' => 'Devuelta',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'returned',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'credit_card' => 'Tarjeta de Crédito',
                        'debit_card' => 'Tarjeta de Débito',
                        'transfer' => 'Transferencia',
                        default => $state,
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        'returned' => 'Devuelta',
                    ]),
                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sale_date', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sale_date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta Débito/Crédito',
                        'transferencia' => 'Transferencia',
                        'otro' => 'Otro',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Sale $record): bool => $record->status !== 'cancelada'),
                Tables\Actions\Action::make('print')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Sale $record): string => route('sales.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Sale $record): bool => $record->status !== 'cancelada')
                    ->action(function (Sale $record) {
                        $record->update(['status' => 'cancelada']);

                        // Se crearán automáticamente los movimientos para restaurar el stock
                        // a través de los observers
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['customer', 'user']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'customer.name', 'user.name'];
    }
}
