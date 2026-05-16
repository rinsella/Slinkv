<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->string('currency', 8)->default('IDR');
            $table->enum('billing_period', ['free', 'monthly', 'yearly'])->default('free');
            $table->unsignedInteger('max_links')->nullable();
            $table->unsignedInteger('max_clicks_per_link')->nullable();
            $table->unsignedInteger('analytics_retention_days')->default(7);
            $table->enum('bot_protection_level', ['none', 'basic', 'advanced'])->default('basic');
            $table->unsignedInteger('geo_filter_limit')->nullable();
            $table->boolean('has_fallback_url')->default(false);
            $table->boolean('has_custom_alias')->default(false);
            $table->boolean('has_qr_code')->default(false);
            $table->boolean('has_export_csv')->default(false);
            $table->boolean('has_audit_report')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
