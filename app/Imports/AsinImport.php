<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AsinImport implements ToArray, WithStartRow
{
    private array $asins = [];

    public function startRow(): int
    {
        return 2; // Skip header row
    }

    public function array(array $array): void
    {
        foreach ($array as $row) {
            if (!empty($row[1])) { // ASIN is in column B (index 1)
                $this->asins[] = trim($row[1]);
            }
        }
    }

    public function getAsins(): array
    {
        return array_filter($this->asins);
    }
}
