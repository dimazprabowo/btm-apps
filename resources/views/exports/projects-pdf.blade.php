<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Proyek</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .header h1 { font-size: 18px; color: #2563eb; margin: 0 0 4px; }
        .header p { font-size: 11px; color: #6b7280; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2563eb; color: #ffffff; padding: 8px 6px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; }
        .badge-active { background-color: #dcfce7; color: #166534; }
        .badge-on_hold { background-color: #fef3c7; color: #92400e; }
        .badge-completed { background-color: #dbeafe; color: #1e40af; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        .footer { text-align: right; margin-top: 15px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Boilerplate') }}</h1>
        <p>Laporan Data Proyek &mdash; {{ now()->format('d F Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 8%;">Kode</th>
                <th style="width: 20%;">Nama Proyek</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 12%;">Pemilik</th>
                <th style="width: 6%;">Tugas</th>
                <th style="width: 6%;">Anggota</th>
                <th style="width: 10%;">Mulai</th>
                <th style="width: 10%;">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $index => $project)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $project->code }}</strong></td>
                    <td>{{ $project->name }}</td>
                    <td>
                        @php
                            $badgeClass = match($project->status->value) {
                                'active' => 'badge-active',
                                'on_hold' => 'badge-on_hold',
                                'completed' => 'badge-completed',
                                'cancelled' => 'badge-cancelled',
                                default => 'badge-active',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ $project->status->label() }}
                        </span>
                    </td>
                    <td>{{ $project->owner?->name ?? '-' }}</td>
                    <td>{{ $project->tasks_count }}</td>
                    <td>{{ $project->members_count }}</td>
                    <td>{{ $project->start_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $project->end_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh: {{ auth()->user()->name }} &mdash; {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
