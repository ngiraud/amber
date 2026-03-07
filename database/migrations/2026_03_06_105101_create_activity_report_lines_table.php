<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_report_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('activity_report_id');
            $table->foreign('activity_report_id')->references('id')->on('activity_reports')->cascadeOnDelete();
            $table->ulid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('minutes')->default(0);
            $table->decimal('days', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['activity_report_id', 'project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_report_lines');
    }
};
