<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de Pedidos - Zona Cero</title>
    <style>
        @include('reportes.css.pdf')
    </style>
</head>
<body>
    <header class="report-header">
        <div class="report-logo">
            <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Zona Cero">
        </div>
        <div class="report-title">

            <h1>Reporte de Pedidos</h1>

            <p class="report-subtitle">
                Estado: <strong>{{ $estado }}</strong>
                Fecha: <strong>{{ $fechaInicio }}</strong> al <strong>{{ $fechaFin }}</strong></p>
        </div>
        <div class="report-meta">
            <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
            Total pedidos: <strong>{{ $totalPedidos }}</strong>
            Total ventas: <strong>${{ number_format($totalVentas, 2, '.', ',') }}</strong>
        </div>
    </header>

    <main class="report-body">
        <table class="report-table">
            <thead>
                <tr>

                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pedidos as $pedido)
                <tr>
                    <td>{{ $pedido->user->name }}</td>
                    <td>{{ $pedido->fecha->format('d/m/Y') }}</td>
                    <td>{{ $pedido->estado }}</td>
                    <td>${{ number_format($pedido->total, 2, '.', ',') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    <footer class="report-footer">
        <p>Zona Cero - Generado por el sistema</p>
    </footer>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('Dejavu Sans', 'normal');
            $pdf->page_text(520, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 9);
        }
    </script>
</body>
</html>
