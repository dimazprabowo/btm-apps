<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected Project $project,
        protected ?string $search = null,
        protected ?string $priorityFilter = null,
        protected ?int $assigneeFilter = null,
    ) {
    }

    public function query()
    {
        return $this->project->tasks()
            ->with(['status', 'assignees:id,name', 'reporter:id,name'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->where('users.id', $this->assigneeFilter)))
            ->orderBy('number');
    }

    public function headings(): array
    {
        return ['No', 'Kode', 'Judul', 'Status', 'Prioritas', 'Penerima', 'Pelapor', 'Mulai', 'Jatuh Tempo', 'Selesai'];
    }

    public function map($task): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $task->code,
            $task->title,
            $task->status?->name ?? '-',
            $task->priority->label(),
            $task->assignees->pluck('name')->implode(', ') ?: '-',
            $task->reporter?->name ?? '-',
            $task->start_date?->format('d/m/Y') ?? '-',
            $task->due_date?->format('d/m/Y') ?? '-',
            $task->completed_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }
}
