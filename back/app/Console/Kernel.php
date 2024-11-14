<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\TestNotifications::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reservations:check-due-dates')
                 ->dailyAt('09:00');
    }

    protected function commandsAreLoaded(): bool
    {
        return false;
    }
}
