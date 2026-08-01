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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('expense'); // expense, income, transfer
            $table->string('icon')->default('tag');
            $table->string('color')->default('#6B7280');
            $table->timestamps();
        });

        $categories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#F59E0B'],
            ['name' => 'Groceries', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#10B981'],
            ['name' => 'Transport', 'type' => 'expense', 'icon' => 'car', 'color' => '#3B82F6'],
            ['name' => 'Utilities & Bills', 'type' => 'expense', 'icon' => 'zap', 'color' => '#8B5CF6'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'film', 'color' => '#EC4899'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#6366F1'],
            ['name' => 'Salary / Income', 'type' => 'income', 'icon' => 'dollar-sign', 'color' => '#22C55E'],
            ['name' => 'Transfer', 'type' => 'transfer', 'icon' => 'arrow-right-left', 'color' => '#64748B'],
        ];

        foreach ($categories as $cat) {
            \App\Models\Category::firstOrCreate(
                ['name' => $cat['name'], 'user_id' => null],
                ['type' => $cat['type'], 'icon' => $cat['icon'], 'color' => $cat['color']]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
