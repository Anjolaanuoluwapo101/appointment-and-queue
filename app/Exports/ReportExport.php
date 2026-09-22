<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One sheet per report group (PRD §17): key/value rows the admin can
 * filter further in Excel.
 */
class ReportExport implements Export, WithMultipleSheets
{
    use Exportable;

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    public function __construct(private array $sheets) {}

    /** @return array<int, FromCollection> */
    public function sheets(): array
    {
        return collect($this->sheets)->map(
            fn ($rows, $name) => new class($rows, (string) $name) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(private array $rows, private string $name) {}

                public function collection(): Collection
                {
                    return collect($this->rows);
                }

                public function headings(): array
                {
                    return ['Metric', 'Value'];
                }

                public function title(): string
                {
                    return mb_substr($this->name, 0, 31);
                }
            }
        )->values()->all();
    }
}
