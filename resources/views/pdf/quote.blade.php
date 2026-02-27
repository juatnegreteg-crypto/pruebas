<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cotizacion #{{ $quote->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            padding: 40px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 11px;
            color: #6b7280;
        }

        .quote-title {
            font-size: 28px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .quote-number {
            font-size: 16px;
            color: #6b7280;
        }

        .quote-date {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info {
            margin-bottom: 30px;
        }

        .info table {
            width: 100%;
            border-collapse: collapse;
        }

        .info td {
            padding: 4px 0;
            vertical-align: top;
        }

        .info td.label {
            width: 18%;
            color: #6b7280;
            font-weight: bold;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table.items thead {
            background-color: #f3f4f6;
        }

        table.items th {
            padding: 12px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }

        table.items th.text-right {
            text-align: right;
        }

        table.items th.text-center {
            text-align: center;
        }

        table.items td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.items td.text-right {
            text-align: right;
        }

        table.items td.text-center {
            text-align: center;
        }

        table.items tr:last-child td {
            border-bottom: none;
        }

        .item-description {
            font-weight: 500;
            color: #1f2937;
        }

        .item-type {
            display: inline-block;
            font-size: 10px;
            padding: 2px 8px;
            background-color: #e5e7eb;
            color: #4b5563;
            border-radius: 4px;
            margin-top: 4px;
        }

        .totals {
            width: 300px;
            float: right;
            margin-top: 20px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 8px 10px;
        }

        .totals td.label {
            text-align: right;
            color: #6b7280;
        }

        .totals td.value {
            text-align: right;
            font-weight: 500;
        }

        .totals tr.total {
            background-color: #1e40af;
            color: white;
        }

        .totals tr.total td {
            font-size: 14px;
            font-weight: bold;
            padding: 12px 10px;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }

        .notes {
            margin-top: 40px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 8px;
        }

        .notes-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .notes-content {
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-name">CDA San Jorge</div>
            <div class="company-info">
                Centro de Diagnostico Automotor<br>
                NIT: 000.000.000-0<br>
                Direccion: Calle Principal #123<br>
                Telefono: (000) 000-0000
            </div>
        </div>
        <div class="header-right">
            <div class="quote-title">COTIZACION</div>
            <div class="quote-number">#{{ str_pad($quote->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="quote-date">
                Fecha: {{ $quote->created_at->format('d/m/Y') }}<br>
                Hora: {{ $quote->created_at->format('H:i') }}
            </div>
        </div>
    </div>

    <div class="info">
        <div class="section-title">Cliente y Vehiculo</div>
        <table>
            <tr>
                <td class="label">Cliente:</td>
                <td>
                    {{ $quote->vehicle?->customer?->full_name ?? 'No disponible' }}
                    @if($quote->vehicle?->customer?->document_number)
                        ({{ $quote->vehicle->customer->document_number }})
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Vehiculo:</td>
                <td>
                    @if($quote->vehicle)
                        {{ $quote->vehicle->plate }} - {{ $quote->vehicle->make }} {{ $quote->vehicle->model }} {{ $quote->vehicle->year }}
                    @else
                        No disponible
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detalle de Items</div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40%;">Descripcion</th>
                <th class="text-center" style="width: 10%;">Cant.</th>
                <th class="text-right" style="width: 15%;">P. Unitario</th>
                <th class="text-center" style="width: 10%;">IVA</th>
                <th class="text-right" style="width: 12%;">Subtotal</th>
                <th class="text-right" style="width: 13%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
            <tr>
                <td>
                    <div class="item-description">{{ $item->description }}</div>
                    <span class="item-type">
                        @if(str_contains($item->itemable_type, 'Product'))
                            Producto
                        @elseif(str_contains($item->itemable_type, 'Service'))
                            Servicio
                        @elseif(str_contains($item->itemable_type, 'Bundle'))
                            Paquete
                        @else
                            Item
                        @endif
                    </span>
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($item->tax_rate, 0) }}%</td>
                <td class="text-right">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">${{ number_format($quote->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">IVA (19%):</td>
                <td class="value">${{ number_format($quote->tax_total, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td class="label">TOTAL:</td>
                <td class="value">${{ number_format($quote->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="notes">
        <div class="notes-title">Notas importantes:</div>
        <div class="notes-content">
            <ul style="margin-left: 15px;">
                <li>Esta cotizacion tiene una validez de 30 dias a partir de la fecha de emision.</li>
                <li>Los precios incluyen IVA del 19%.</li>
                <li>El tiempo de entrega esta sujeto a disponibilidad.</li>
                <li>Forma de pago: Contado / Credito (segun acuerdo).</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado automaticamente por el sistema CDA San Jorge</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
