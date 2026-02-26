<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('project_id');
            $table->unsignedInteger('rounded_minutes')->nullable()->after('duration_minutes');
            $table->text('description')->nullable()->after('notes');
        });

        Schema::dropIfExists('time_entries');
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['date', 'rounded_minutes', 'description']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('session_id')->nullable()->index();
            $table->ulid('project_id')->index();
            $table->date('date');
            $table->datetime('started_at');
            $table->datetime('ended_at');
            $table->unsignedInteger('raw_minutes');
            $table->unsignedInteger('rounded_minutes');
            $table->unsignedTinyInteger('source');
            $table->text('description')->nullable();
            $table->boolean('is_validated')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'date']);
        });
    }
};
