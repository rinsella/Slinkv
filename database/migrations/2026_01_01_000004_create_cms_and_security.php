<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'replied'])->default('unread');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 32)->default('text');
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('blocked_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64)->unique();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['user_agent_contains', 'ip_rate', 'header_missing', 'country', 'referer', 'custom']);
            $table->string('pattern')->nullable();
            $table->unsignedSmallInteger('score')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'rewarded'])->default('pending');
            $table->unsignedBigInteger('reward_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reporter_email')->nullable();
            $table->foreignId('short_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('short_url')->nullable();
            $table->text('reason');
            $table->enum('status', ['open', 'reviewed', 'closed'])->default('open');
            $table->text('admin_action')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_reports');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bot_rules');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('blocked_domains');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('articles');
    }
};
