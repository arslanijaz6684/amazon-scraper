<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
//use App\Exports\AsinDataExport;
use App\Imports\AsinImport;
use Illuminate\Support\Facades\Storage;

class ExcelService
{
    /**
     * Import ASINs from Excel file
     */
    public function importAsins(string $filePath): array
    {
        $import = new AsinImport();
        Excel::import($import, $filePath);

        return $import->getAsins();
    }

}
