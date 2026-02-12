<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsinDataExport implements FromArray, WithHeadings, WithEvents, WithStyles
{
    protected array $data;
    protected array $mergeRanges = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'ASIN',
            'Manufacturer Name',
            'Manufacturer Address',
            'Manufacturer Email',
            'Responsible Name',
            'Responsible Address',
            'Responsible Email',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rowIndex = 2; // row 1 = heading

        foreach ($this->data as $item) {

            $manufacturers = is_array(reset($item['manufacturer']))
                ? $item['manufacturer']
                : [$item['manufacturer']];

            $responsible = is_array(reset($item['responsible']))
                ? $item['responsible']
                : [$item['responsible']];

            $maxRows = max(count($manufacturers), count($responsible));
            $startRow = $rowIndex;

            for ($i = 0; $i < $maxRows; $i++) {
                $rows[] = [
                    $i === 0 ? $item['asin'] : '',

                    $manufacturers[$i]['name'] ?? '',
                    ltrim($manufacturers[$i]['address'] ?? '',','),
                    $manufacturers[$i]['email'] ?? '',

                    $responsible[$i]['name'] ?? '',
                    ltrim($responsible[$i]['address'] ?? '',','),
                    $responsible[$i]['email'] ?? '',
                ];

                $rowIndex++;
            }

            if ($maxRows > 1) {
                $this->mergeRanges[] = "A{$startRow}:A" . ($rowIndex - 1);
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->mergeRanges as $range) {
                    $event->sheet->mergeCells($range);
                    $event->sheet->getStyle($range)->getAlignment()->setVertical('center');
                }
            },
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],

            // Set column widths
            'A' => ['width' => 100],
            'B' => ['width' => 150],
            'C' => ['width' => 3000],
            'D' => ['width' => 400],
            'E' => ['width' => 250],
            'F' => ['width' => 300],
            'G' => ['width' => 400],
            'H' => ['width' => 250],
        ];
    }
}
