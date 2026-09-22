<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1, h2 { color: #333; }
    </style>
</head>
<body>
    <h1>Report ({{ $filters['from'] }} to {{ $filters['to'] }})</h1>
    
    @foreach($report as $section => $data)
        <h2>{{ ucfirst($section) }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $key => $value)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td>{{ is_array($value) ? json_encode($value) : (string)$value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
