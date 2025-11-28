<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncSMSFiles::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Synchronisation automatique toutes les minutes
        $schedule->command('sms:sync-files')->everyMinute();
        
        // Nettoyage des anciens fichiers traités une fois par jour
        $schedule->command('sms:cleanup-old-files')->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}