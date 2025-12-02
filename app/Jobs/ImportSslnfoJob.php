<?php

namespace App\Jobs;

use App\Models\SslInfo;
use App\Services\OpenSslService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class ImportSslnfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $domain;

    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $s = new OpenSslService();
        $result = $s->getInfo($this->domain);
        if (empty($result)) {
            Log::error('array is empty : ' . serialize($result));
            throw new Exception('array is empty!');
        }

        SslInfo::insert($result);
        echo PHP_EOL . PHP_EOL . "inserted!" . PHP_EOL . PHP_EOL;

    }
}
