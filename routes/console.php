<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::timezone('Europe/Paris')->group(function () {
    Schedule::command('menubar:update-timer')->everyMinute();
    Schedule::command('activity:scan')->everyMinute();
    Schedule::command('sessions:reconstruct')->everyFiveMinutes();
    Schedule::command('session:check-reminder')->everyMinute();
});
