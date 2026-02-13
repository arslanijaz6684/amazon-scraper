<?php

namespace App\Jobs;

use App\Models\AsinsData;
use App\Models\ScrapeJob;
use App\Services\AmazonScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScrapeAmazonJob implements ShouldQueue
{
    use Queueable;

    protected array $asinsData;
    protected int $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $asinsData)
    {
        $this->asinsData = $asinsData;
        $this->jobId = (int)$jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(AmazonScraperService $scraperService): void
    {
        $jobEntry = ScrapeJob::find($this->jobId);
        try {
            $jobEntry->update(['status' => 'processing']);
            $result = $scraperService->processAsins($this->asinsData);
            $result = collect($result)->map(function ($item) {
                if (count($item['manufacturer']) === 0) {
                    $item['manufacturer'] = $this->getEmptyData();
                }
                if (count($item['responsible']) === 0) {
                    $item['responsible'] = $this->getEmptyData();
                }
                $item['manufacturer'] = json_encode($item['manufacturer'] ?? []);
                $item['responsible'] = json_encode($item['responsible'] ?? []);
                return $item;
            })->toArray();
            $jobEntry->update(['status' => 'done']);
            AsinsData::upsert($result, ['asin'], ['manufacturer', 'responsible.']);
        } catch (\Exception $e) {
            $jobEntry->update(['status' => 'failed']);
        }
    }

    private function getEmptyData(): array
    {
        return [
            'name ' => 'Not available',
            'address ' => 'Not available',
            'phone ' => 'Not available',
            'email ' => 'Not available'
        ];
    }
}
