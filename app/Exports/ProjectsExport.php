<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected User $user;
    protected bool $viewAll;
    protected ?string $search;
    protected ?string $statusFilter;

    public function __construct(User $user, bool $viewAll = false, ?string $search = null, ?string $statusFilter = null)
    {
        $this->user = $user;
        $this->viewAll = $viewAll;
        $this->search = $search;
        $this->statusFilter = $statusFilter;
    }

    public function query()
    {
        $query = Project::query()
            ->with('owner:id,name')
            ->withCount(['tasks', 'members']);

        if (! $this->viewAll) {
            $query->forUser($this->user);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereLike('code', $this->search)
                  ->orWhereLike('name', $this->search);
            });
        }

        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode',
            'Nama Proyek',
            'Deskripsi',
            'Status',
            'Warna',
            'Pemilik',
            'Jumlah Tugas',
            'Jumlah Anggota',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Dibuat Pada',
        ];
    }

    public function map($project): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $project->code,
            $project->name,
            $project->description ?? '-',
            $project->status->label(),
            $project->color,
            $project->owner?->name ?? '-',
            $project->tasks_count,
            $project->members_count,
            $project->start_date?->format('d/m/Y') ?? '-',
            $project->end_date?->format('d/m/Y') ?? '-',
            $project->created_at->format('d/m/Y H:i'),
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
