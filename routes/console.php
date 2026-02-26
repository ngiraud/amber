<?php

declare(strict_types=1);

use App\Jobs\EndOfDayReconstructionJob;
use Illuminate\Support\Facades\Schedule;

Schedule::timezone('Europe/Paris')->group(function () {
    Schedule::command('menubar:update-timer')->everyMinute();
    Schedule::command('activity:scan')->everyMinute();
    Schedule::command('activity:check-idle')->everyMinute();
    Schedule::command('activity:check-untracked')->everyMinute();
    Schedule::job(new EndOfDayReconstructionJob)->dailyAt('00:05');
});
