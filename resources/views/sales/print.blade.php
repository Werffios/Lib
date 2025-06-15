<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta #{{ $sale->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            margin-bottom: 5px;
        }
        .invoice-details {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .invoice-details div {
            flex-basis: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            button.no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="position: absolute; right: 20px; top: 20px; padding: 8px 15px;">Imprimir</button>

    <div class="invoice-header">
        <h1>Librería Central</h1>
        <p>Av. Reforma #123, Ciudad de México</p>
        <p>Tel: (55) 1234-5678 | Email: contacto@libreriacentral.com</p>
        <h2>Comprobante de Venta</h2>
    </div>

    <div class="invoice-details">
        <div>
            <p><strong>Factura/Ticket:</strong> {{ $sale->invoice_number }}</p>
            <p><strong>Fecha:</strong> {{ $sale->sale_date->format('d/m/Y H:i') }}</p>
            <p><strong>Vendedor:</strong> {{ $sale->user->name }}</p>
            <p><strong>Método de Pago:</strong> {{ $sale->payment_method == 'cash' ? 'Efectivo' : ($sale->payment_method == 'credit_card' ? 'Tarjeta de Crédito' : ($sale->payment_method == 'debit_card' ? 'Tarjeta de Débito' : 'Transferencia')) }}</p>
        </div>
        <div>
            <p><strong>Cliente:</strong> {{ $sale->customer ? $sale->customer->name : 'Cliente Ocasional' }}</p>
            @if($sale->customer && $sale->customer->email)
                <p><strong>Email:</strong> {{ $sale->customer->email }}</p>
            @endif
            @if($sale->customer && $sale->customer->phone)
                <p><strong>Teléfono:</strong> {{ $sale->customer->phone }}</p>
            @endif
            @if($sale->customer && $sale->customer->address)
                <p><strong>Dirección:</strong> {{ $sale->customer->address }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Libro</th>
                <th>ISBN</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->book->title }}</td>
                    <td>{{ $item->book->ISBN }}</td>
                    <td>{{ number_format($item->price, 2) }} MXN</td>
                    <td>{{ number_format($item->subtotal, 2) }} MXN</td>
                </tr>
            @endforeach

            @if($sale->discount_amount > 0)
                <tr>
                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                    <td>{{ number_format($sale->total_amount + $sale->discount_amount, 2) }} MXN</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right"><strong>Descuento:</strong></td>
                    <td>{{ number_format($sale->discount_amount, 2) }} MXN</td>
                </tr>
            @endif

            @if($sale->tax_amount > 0)
                <tr>
                    <td colspan="4" class="text-right"><strong>IVA:</strong></td>
                    <td>{{ number_format($sale->tax_amount, 2) }} MXN</td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                <td>{{ number_format($sale->total_amount, 2) }} MXN</td>
            </tr>
        </tbody>
    </table>

    @if($sale->notes)
        <div>
            <h3>Notas:</h3>
            <p>{{ $sale->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Gracias por su compra. ¡Esperamos verle pronto!</p>
        <p>Impreso el: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Este documento no es un comprobante fiscal.</p>

        @if($sale->status == 'cancelled')
            <p style="color: red; font-weight: bold; font-size: 16px; margin-top: 20px;">VENTA CANCELADA</p>
        @endif
    </div>
</body>
</html>

