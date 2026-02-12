<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AmazonScraperService
{
    public function processAsins(array $asins): array
    {
        if (!isset($asins[0]['ASIN'])) {
            $asins = collect($asins)->map(function ($asin) {
                return ['ASIN' => $asin];
            })->toArray();
        }
        // Data you want to send to Node.js
        $dataToSend = json_encode($asins);
        $scriptPath = base_path('scripts/scrape.js');

        // Method using shell_exec for simplicity:
        $command = "node \"$scriptPath\" 2>&1 " . addslashes($dataToSend);
        $output = shell_exec($command);
        return json_decode($output, true);
    }
}

