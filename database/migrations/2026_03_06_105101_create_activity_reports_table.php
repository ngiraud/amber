<?php

declare(strict_types=1);

use App\Enums\ActivityReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('client_id')->index();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('status')->default(ActivityReportStatus::Draft->value);
            $table->unsignedInteger('total_minutes')->default(0);
            $table->decimal('total_days', 6, 2)->default(0);
            $table->unsignedBigInteger('total_amount_ht')->nullable();
            $table->datetime('generated_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('csv_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};
