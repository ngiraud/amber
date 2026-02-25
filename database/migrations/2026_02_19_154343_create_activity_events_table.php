<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('project_id')->index();
            $table->ulid('project_repository_id')->index();
            $table->ulid('session_id')->nullable()->index();
            $table->string('source_type');
            $table->string('type');
            $table->datetime('occurred_at');
            $table->json('metadata');
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);

            // Used by firstOrCreate deduplication lookup
            $table->index(['project_id', 'project_repository_id', 'type', 'source_type', 'occurred_at'], 'activity_events_dedup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
