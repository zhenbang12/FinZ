<?php

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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "YouTube Premium Payment Sheets"
            $table->integer('billing_cycle_day')->default(27); // Day of month cycle starts (e.g. 27)
            $table->decimal('total_monthly_cost', 10, 2)->default(0.00);
            $table->string('currency')->default('MYR');
            $table->text('notes')->nullable(); // e.g. "27th of Every Month Starts a new cycle"
            $table->timestamps();
        });

        Schema::create('subscription_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Melissa", "Waiz", "Tan Jing", "Hong Yu"
            $table->decimal('default_share_amount', 10, 2)->default(7.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_member_id')->constrained('subscription_members')->cascadeOnDelete();
            $table->integer('billing_year'); // e.g. 2025, 2026
            $table->integer('billing_month'); // 1 - 12
            $table->string('billing_cycle_label')->nullable(); // e.g. "October 2025"
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending'); // 'paid', 'pending', 'waived'
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('reference_no')->nullable(); // TNG reference ID e.g. 2025103010110000010000TNGOW3
            $table->text('notes')->nullable(); // Transfer details e.g. "Receive from Wallet..."
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_members');
        Schema::dropIfExists('subscriptions');
    }
};
