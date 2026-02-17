<?php

namespace App\Console\Commands;

use App\Http\Controllers\FileController;
use App\Models\AsinsData;
use App\Services\AmazonScraperService;
use Illuminate\Console\Command;

class ScrapAmazonCommand extends Command
{
    protected $signature = 'scrap:amazon-asins';

    protected $description = 'Scrap Amazon Asin Data (Manufacturer and Responsible)';

    public function handle(): void
    {
        $scraperService = app(AmazonScraperService::class);
        $asins = AsinsData::where(function ($query) {
            $query->whereNull('manufacturer')->orWhereNull('responsible');
        })->selectRaw('asin as ASIN')->get()->toArray();
        $data = array_chunk($asins, 100);
        $results = [];
        $this->info('start');
        $i = 0;
        $this->info(count($data));
        foreach ($data as $asins) {
            $this->info('Processing: ' . $i);
            $result = $scraperService->processAsins($asins);
            $this->info('Finish Processing: ' . $i++);
            $this->info('Result: ' . json_encode($result));
            $results = array_merge($results, $result);
            \Log::info('Scrap Data: ', $result);
        }
    }
}
