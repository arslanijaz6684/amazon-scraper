<?php

namespace App\Http\Controllers;

use App\Exports\AsinDataExport;
use App\Jobs\ScrapeAmazonJob;
use App\Models\AsinsData;
use App\Models\ScrapeJob;
use App\Services\AmazonScraperService;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;

class FileController extends Controller
{
    protected AmazonScraperService $scraperService;
    protected ExcelService $excelService;

    public function __construct(AmazonScraperService $scraperService, ExcelService $excelService)
    {
        $this->excelService = $excelService;
        $this->scraperService = $scraperService;
    }

    public function index()
    {
        return view('index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        try {
            $filePath = $request->file('file')->store('uploads');
            $fullPath = Storage::disk('local')->path($filePath);
            $result = $this->excelService->importAsins($fullPath);
            $result = collect($result)->map(function ($item) {
                return ['asin' => $item];
            })->toArray();
            AsinsData::insertOrIgnore($result);
            Storage::disk('local')->delete($filePath);
            return back()->with(['success' => 'ASINs upload successfully']);
        } catch (\Throwable $e) {
            return back()->with(['error' => 'File Upload failed. Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }

    public function scrap()
    {
        try {
            $asins = AsinsData::where(function ($query) {
                $query->whereNull('manufacturer')->orWhereNull('responsible');
            })->selectRaw('asin as ASIN')->get()->toArray();
            if (count($asins) > 0) {
                // Create a scrape job entry
                $jobEntry = ScrapeJob::create(['status' => 'pending']);

                // Dispatch the Job with job ID
                $data = array_chunk($asins, 100);
                $jobs = [];
                foreach ($data as $asins) {
                    $jobs[] = new ScrapeAmazonJob($jobEntry->id, $asins);
                }
                \Bus::chain($jobs)->dispatch();
                session()->put('scrape_job_id', $jobEntry->id);
                return back()->with(['success' => 'Scraping started!']);
            }else{
                return back()->with(['success' => 'All ASINs data already fetched.']);
            }
        } catch (\Throwable $e) {
            return back()->with(['error' => 'ASINs Processing Failed. Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }


    public function downloadNew()
    {
        try {
            $asinsData = AsinsData::select('asin', 'manufacturer', 'responsible')->whereNotNull('manufacturer')
                ->whereNotNull('responsible')
                ->whereNew(false)
                ->get();
            if ($asinsData->count() === 0) {
                return back()->with(['error' => 'New ASINs data not found']);
            }
            AsinsData::whereNew(false)->update(['new' => true]);
            return Excel::download(new AsinDataExport($asinsData->toArray()), 'asin_results_' . date('Y_m_d_H_i_s') . '.xlsx');
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return back()->with(['error' => ' Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }

    public function download()
    {
        try {
            $asinsData = AsinsData::select('asin', 'manufacturer', 'responsible')->whereNotNull('manufacturer')
                ->whereNotNull('responsible')
                ->get();
            if ($asinsData->count() === 0) {
                return back()->with(['error' => 'ASINs data not found']);
            }
            return Excel::download(new AsinDataExport($asinsData->toArray()), 'asin_results_' . date('Y_m_d_H_i_s') . '.xlsx');
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return back()->with(['error' => ' Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }
}
