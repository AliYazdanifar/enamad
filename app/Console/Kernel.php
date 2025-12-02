<?php

namespace App\Console;

use App\Jobs\ImportDetailJob;
use App\Jobs\ImportEmailJob;
use App\Jobs\ImportSslnfoJob;
use App\Models\DomainEmail;
use App\Services\EnamadService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
//         $schedule->command('inspire')->hourly();
//        $schedule->job(new ImportEmailJob())->everyFiveSeconds();

        $schedule->call(function () {
//            $this->getEmails();
//            $this->getDetails();
            $this->getSSLInfo();
        });

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    protected function getEmails()
    {
        $endPage = 12449;
        for ($i = 1; $i <= $endPage; $i++) {
            ImportEmailJob::dispatch($i);
        }
    }

    protected function getDetails()
    {
        $endPage = 12449;
        for ($i = 1; $i <= $endPage; $i++) {
            ImportDetailJob::dispatch($i);
        }
    }

    protected function getSSLInfo()
    {
        $domains = DomainEmail::all();
        foreach ($domains as $domain) {
            ImportSslnfoJob::dispatch($domain->domain)->onConnection('ssl');
        }
    }


}
