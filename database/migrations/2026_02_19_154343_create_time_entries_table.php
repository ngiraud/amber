<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
