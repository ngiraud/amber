<?php

declare(strict_types=1);

use App\Actions\Settings\ResetDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

pest()->group('settings', 'database');

describe('reset database', function () {
    it('delegates POST to ResetDatabase and redirects to home', function () {
        ResetDatabase::fake()
            ->shouldReceive('handle')
            ->once();

        $this->post(route('settings.reset'))
            ->assertRedirectToRoute('home')
            ->assertInertiaFlash('success', 'All data has been reset successfully.');
    });
})->group('controllers');

describe('ResetDatabase action', function () {
    it('disconnects the database, deletes files, and runs migrations', function () {
        $dbPath = tempnam(sys_get_temp_dir(), 'test_db');
        file_put_contents("{$dbPath}-wal", '');
        file_put_contents("{$dbPath}-shm", '');

        $connection = Mockery::mock();
        $connection->shouldReceive('getDatabaseName')->andReturn($dbPath);
        DB::shouldReceive('connection')->andReturn($connection);
        DB::shouldReceive('disconnect')->once();

        Artisan::spy();

        ResetDatabase::make()->handle();

        expect(file_exists($dbPath))->toBeFalse()
            ->and(file_exists("{$dbPath}-wal"))->toBeFalse()
            ->and(file_exists("{$dbPath}-shm"))->toBeFalse();

        Artisan::shouldHaveReceived('call')
            ->with('migrate', ['--force' => true])
            ->once();
    });

    it('skips deletion for files that do not exist', function () {
        $dbPath = '/tmp/nonexistent_test_db_'.uniqid();

        $connection = Mockery::mock();
        $connection->shouldReceive('getDatabaseName')->andReturn($dbPath);
        DB::shouldReceive('connection')->andReturn($connection);
        DB::shouldReceive('disconnect')->once();

        Artisan::spy();

        // Should not throw even when no files exist
        ResetDatabase::make()->handle();

        Artisan::shouldHaveReceived('call')->once();
    });
})->group('actions');
