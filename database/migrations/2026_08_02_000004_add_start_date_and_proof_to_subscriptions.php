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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('start_month')->default(8)->after('billing_cycle_day'); // 1 - 12
            $table->integer('start_year')->default(2026)->after('start_month');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('proof_image_path')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['start_month', 'start_year']);
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn('proof_image_path');
        });
    }
};
