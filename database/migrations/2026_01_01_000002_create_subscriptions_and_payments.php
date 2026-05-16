<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('IDR');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'])->default('pending')->index();
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
