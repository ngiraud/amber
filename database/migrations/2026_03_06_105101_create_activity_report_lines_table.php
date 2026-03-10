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
            $table->ulid('activity_report_id')->index();
            $table->ulid('project_id')->index();
            $table->date('date');
            $table->unsignedInteger('minutes')->default(0);
            $table->decimal('days', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->unique(['activity_report_id', 'project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_report_lines');
    }
};
