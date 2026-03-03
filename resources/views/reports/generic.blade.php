<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #10274C; padding-bottom: 10px; }
        .header h1 { color: #10274C; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #10274C; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; padding: 10px 0; }
        .summary { margin-top: 30px; text-align: right; font-weight: bold; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; color: white; }
        .bg-success { background-color: #28a745; }
        .bg-danger { background-color: #dc3545; }
        .bg-warning { background-color: #ffc107; color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Biblioteca - Reporte Profesional</h1>
        <p>{{ $title }}</p>
        <p>Fecha de generación: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        Total de registros: {{ count($data) }}
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} Biblioteca Profesional. Generado automáticamente por el Sistema de Auditoría.
    </div>
</body>
</html>
