<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->text('destination_url');
            $table->text('fallback_url')->nullable();
            $table->boolean('bot_protection_enabled')->default(true);
            $table->boolean('geo_filter_enabled')->default(false);
            $table->json('allowed_countries')->nullable();
            $table->json('blocked_countries')->nullable();
            $table->enum('device_filter', ['all', 'desktop', 'mobile', 'tablet'])->default('all');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->unsignedBigInteger('human_clicks')->default(0);
            $table->unsignedBigInteger('bot_clicks')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
        });

        Schema::create('click_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('ip_address_encrypted')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->string('country_code', 8)->nullable()->index();
            $table->string('country_name')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('source_platform', 64)->nullable()->index();
            $table->string('source_id')->nullable();
            $table->boolean('is_bot')->default(false)->index();
            $table->unsignedSmallInteger('bot_score')->default(0);
            $table->json('bot_reasons')->nullable();
            $table->enum('action', ['redirected', 'blocked', 'fallback', 'expired', 'quota_exceeded', 'password_required'])->default('redirected');
            $table->text('redirected_to')->nullable();
            $table->timestamp('clicked_at')->index();
            $table->timestamp('created_at')->nullable();
            $table->index(['short_link_id', 'clicked_at']);
            $table->index(['user_id', 'clicked_at']);
        });

        Schema::create('link_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->unsignedBigInteger('human_clicks')->default(0);
            $table->unsignedBigInteger('bot_clicks')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->timestamps();
            $table->unique(['short_link_id', 'date']);
        });

        Schema::create('link_hourly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->dateTime('datetime_hour');
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->unsignedBigInteger('human_clicks')->default(0);
            $table->unsignedBigInteger('bot_clicks')->default(0);
            $table->timestamps();
            $table->unique(['short_link_id', 'datetime_hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_hourly_stats');
        Schema::dropIfExists('link_daily_stats');
        Schema::dropIfExists('click_logs');
        Schema::dropIfExists('short_links');
    }
};
