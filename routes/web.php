<?php

use App\Http\Controllers\FileController;
use App\Jobs\ScrapeAmazonJob;
use App\Models\AsinsData;
use App\Models\ScrapeJob;
use App\Services\AmazonScraperService;
use Illuminate\Support\Facades\Route;

Route::controller(FileController::class)->group(function () {
    Route::get('/','index')->name('index');
    Route::post('/upload','upload')->name('upload');
    Route::post('/scrap','scrap')->name('scrap');
    Route::post('/download','download')->name('download');
    Route::post('/download-new','downloadNew')->name('download.new');
    Route::get('/scrape-status', function() {
        $job = ScrapeJob::find(session()->get('scrape_job_id'));
        if ($job) {
            return response()->json(['status' => $job->status]);
        }else{
            return response()->json(['status' => '404']);
        }

    });

    Route::get('/test', function(AmazonScraperService $scraperService) {
//        $asins = AsinsData::where(function ($query) {
//            $query->whereNull('manufacturer')->orWhereNull('responsible');
//        })->selectRaw('asin as ASIN')->get()->toArray();
        $asins = [
            ['ASIN' => 'B07XHL9S65']
        ];

            $result = $scraperService->processAsins($asins);

        dd($result);
        // Dispatch the Job with job ID

    });
});

