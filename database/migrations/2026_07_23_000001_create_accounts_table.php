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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('bank'); // bank, e-wallet, cash, credit_card
            $table->string('currency', 10)->default('MYR');
            $table->decimal('initial_balance', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('color')->default('#3B82F6');
            $table->string('icon')->default('wallet');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
