<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetDatabase extends Action
{
    public function handle(): void
    {
        $databasePath = DB::connection()->getDatabaseName();

        DB::disconnect();

        foreach ([$databasePath, "{$databasePath}-wal", "{$databasePath}-shm"] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        Artisan::call('migrate', ['--force' => true]);
    }
}
