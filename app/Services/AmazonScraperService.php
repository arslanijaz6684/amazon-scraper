<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

class AmazonScraperService
{
    public function processAsins(array $asins): array
    {
        try {

            if (!isset($asins[0]['ASIN'])) {
                $asins = collect($asins)->map(function ($asin) {
                    return ['ASIN' => $asin];
                })->toArray();
            }
            // Data you want to send to Node.js
            $dataToSend = json_encode($asins);
            $tempFile = storage_path('app/asins.json');

            file_put_contents($tempFile, $dataToSend);

            $scriptPath = base_path('scripts/scrape.js');

            // Method using shell_exec for simplicity:
            $command = "node \"$scriptPath\"  \"$tempFile\" 2>&1";
            $output = shell_exec($command);
            $data = json_decode($output, true);
            if (is_array($data)) {
                return $data;
            }else{
                throw new \Exception($output);
            }
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());
            return [];
        }
    }
}

