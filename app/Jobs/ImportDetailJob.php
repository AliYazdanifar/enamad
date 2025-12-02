<?php

namespace App\Jobs;

use App\Models\DomainEmailWithDetails;
use App\Services\EnamadService2;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $page;

    /**
     * Create a new job instance.
     */
    public function __construct($page)
    {
        $this->page = $page;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $enamad = new EnamadService2();
        $result = $enamad->getEmails($this->page);

        if (empty($result)) {
            Log::error('array is empty : ' . serialize($result));
            throw new \Exception('array is empty!');
        }

        DomainEmailWithDetails::insert($result);

        echo PHP_EOL . "inserted!" . PHP_EOL . PHP_EOL;
    }
}
