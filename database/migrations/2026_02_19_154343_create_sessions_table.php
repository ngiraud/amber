<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('project_id')->index();
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('source');
            $table->text('notes')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'started_at']);
            $table->index(['project_id', 'is_validated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
