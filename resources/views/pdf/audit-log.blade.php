<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; word-break: break-all; }
        th { background-color: #f2f2f2; }
        h1 { color: #333; font-size: 18px; }
    </style>
</head>
<body>
    <h1>Audit Log</h1>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Subject</th>
                <th>Before</th>
                <th>After</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry->created_at->toDateTimeString() }}</td>
                    <td>{{ $entry->user->name ?? 'system' }}</td>
                    <td>{{ $entry->action }}</td>
                    <td>{{ $entry->subject_type ? class_basename($entry->subject_type).'#'.$entry->subject_id : '—' }}</td>
                    <td>{{ json_encode($entry->before) }}</td>
                    <td>{{ json_encode($entry->after) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
