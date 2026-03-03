<?php

declare(strict_types=1);

use App\Enums\RoundingStrategy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        $now = now();

        DB::table('app_settings')->insert([
            ['key' => 'git_author_emails', 'value' => json_encode([], JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_name', 'value' => json_encode(null, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_address', 'value' => json_encode(null, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_hourly_rate', 'value' => json_encode(null, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_daily_rate', 'value' => json_encode(null, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_daily_reference_hours', 'value' => json_encode(7, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_rounding_strategy', 'value' => json_encode(RoundingStrategy::Quarter->value, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'github_username', 'value' => json_encode(null, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
