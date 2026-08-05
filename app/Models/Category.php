<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Self-healing system category initializer.
     */
    public static function ensureSystemCategories(): void
    {
        $systemCategories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#F59E0B'],
            ['name' => 'Groceries', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#10B981'],
            ['name' => 'Transport & Petrol', 'type' => 'expense', 'icon' => 'car', 'color' => '#3B82F6'],
            ['name' => 'Utilities & Bills', 'type' => 'expense', 'icon' => 'zap', 'color' => '#8B5CF6'],
            ['name' => 'Entertainment & Tech', 'type' => 'expense', 'icon' => 'film', 'color' => '#EC4899'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#6366F1'],
            ['name' => 'Salary / Income', 'type' => 'income', 'icon' => 'dollar-sign', 'color' => '#22C55E'],
            ['name' => 'Payback & Reimbursements', 'type' => 'income', 'icon' => 'users', 'color' => '#06B6D4'],
            ['name' => 'Refunds & Cashbacks', 'type' => 'income', 'icon' => 'rotate-ccw', 'color' => '#8B5CF6'],
            ['name' => 'Investment & Interest', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#10B981'],
            ['name' => 'Transfer', 'type' => 'transfer', 'icon' => 'arrow-right-left', 'color' => '#64748B'],
        ];

        foreach ($systemCategories as $cat) {
            static::firstOrCreate(
                ['name' => $cat['name'], 'user_id' => null],
                ['type' => $cat['type'], 'icon' => $cat['icon'], 'color' => $cat['color']]
            );
        }
    }
}
