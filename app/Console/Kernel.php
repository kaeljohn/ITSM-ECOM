<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── CRM Automation ────────────────────────────────────────────

        // Flag abandoned carts every hour
        $schedule->command('crm:flag-abandoned-carts --hours=2')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crm-abandoned-carts.log'));

        // Re-evaluate RFM scores and auto-segment assignments daily
        $schedule->command('crm:evaluate-rfm', ['--client-id' => 13])
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crm-evaluate-rfm.log'));

        // Re-evaluate churn risk labels daily
        $schedule->command('crm:evaluate-churn', ['--client-id' => 13])
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crm-evaluate-churn.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
