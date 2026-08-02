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
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'start_month')) {
                    $table->integer('start_month')->default(8); // 1 - 12
                }
                if (!Schema::hasColumn('subscriptions', 'start_year')) {
                    $table->integer('start_year')->default(2026);
                }
            });
        }

        if (Schema::hasTable('subscription_payments')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_payments', 'proof_image_path')) {
                    $table->string('proof_image_path')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['start_month', 'start_year']);
            });
        }

        if (Schema::hasTable('subscription_payments')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->dropColumn('proof_image_path');
            });
        }
    }
};
