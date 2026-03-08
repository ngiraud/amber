<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use Illuminate\Console\Command;
use Illuminate\Process\Pipe;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;

#[AsCommand(name: 'debug:export-activity-events')]
class ExportActivityEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:export-activity-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export activity events to a JSON file for local debugging purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! app()->isLocal()) {
            return Command::FAILURE;
        }

        intro('Starting export of activity events table.');

        spin(function () {
            $tableColumns = collect(Schema::getColumns('activity_events'))
                ->pluck('name')
                ->filter(fn (string $column) => ! in_array($column, ['session_id']));

            Process::pipe(function (Pipe $pipe) use ($tableColumns) {
                $pipe->command([
                    'sqlite3',
                    '-json',
                    'database/nativephp.sqlite',
                    sprintf('SELECT %s FROM activity_events;', $tableColumns->join(', ')),
                ]);
                $pipe->command(sprintf("jq '.' > %s", storage_path('sample-data/activity-events.json')));
            })->throw();
        }, 'Exporting activity events to JSON file...');

        outro('Activity events table successfully exported.');
    }
}
