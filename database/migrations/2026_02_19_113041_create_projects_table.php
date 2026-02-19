<?php

declare(strict_types=1);

use App\Enums\RoundingStrategy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('client_id')->index();
            $table->string('name');
            $table->string('color', 7);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('daily_reference_hours')->default(7);
            $table->unsignedTinyInteger('rounding')->default(RoundingStrategy::Quarter);
            $table->unsignedInteger('hourly_rate')->nullable()->comment('Hourly rate in cents');
            $table->unsignedInteger('daily_rate')->nullable()->comment('Daily rate in euros');
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
