<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Flat audit trail export (PRD §19).
 */
class AuditExport implements FromCollection, WithHeadings
{
    /** @param array<int, array<int, mixed>> $rows */
    public function __construct(private array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['Time', 'User', 'Action', 'Record', 'Before', 'After'];
    }
}
