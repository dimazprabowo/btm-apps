<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Tugas</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .header h1 { font-size: 18px; color: #2563eb; margin: 0 0 4px; }
        .header p { font-size: 11px; color: #6b7280; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2563eb; color: #ffffff; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { text-align: right; margin-top: 15px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project->name }} ({{ $project->code }})</h1>
        <p>Daftar Tugas &mdash; {{ now()->format('d F Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Kode</th>
                <th style="width: 28%;">Judul</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 10%;">Prioritas</th>
                <th style="width: 18%;">Penerima</th>
                <th style="width: 10%;">Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $index => $task)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $task->code }}</strong></td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->status?->name ?? '-' }}</td>
                    <td>{{ $task->priority->label() }}</td>
                    <td>{{ $task->assignees->pluck('name')->implode(', ') ?: '-' }}</td>
                    <td>{{ $task->due_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Total: {{ $tasks->count() }} tugas</div>
</body>
</html>
