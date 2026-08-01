<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'billing_cycle_day',
        'start_month',
        'start_year',
        'total_monthly_cost',
        'currency',
        'notes',
    ];

    protected $casts = [
        'total_monthly_cost' => 'decimal:2',
        'billing_cycle_day' => 'integer',
        'start_month' => 'integer',
        'start_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $table = $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(SubscriptionMember::class)->orderBy('name');
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(SubscriptionPayment::class, SubscriptionMember::class);
    }
}
