<?php

namespace App\Jobs;

use App\Models\DomainEmail;
use App\Services\EnamadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class ImportEmailJob implements ShouldQueue
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
        $enamad = new EnamadService();
            $result = $enamad->getEmails($this->page);

            if (empty($result)) {
                Log::error('array is empty : ' . serialize($result));
                throw new Exception('array is empty!');
            }

            DomainEmail::insert($result);

            echo "inserted!";
    }
}
