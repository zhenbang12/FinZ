<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'name',
        'default_share_amount',
        'notes',
    ];

    protected $casts = [
        'default_share_amount' => 'decimal:2',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class)->orderBy('billing_year', 'desc')->orderBy('billing_month', 'desc');
    }
}
