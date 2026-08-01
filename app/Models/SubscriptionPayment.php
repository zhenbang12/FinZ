<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_member_id',
        'billing_year',
        'billing_month',
        'billing_cycle_label',
        'due_date',
        'payment_date',
        'status',
        'amount',
        'reference_no',
        'notes',
        'proof_image_path',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
        'billing_year' => 'integer',
        'billing_month' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(SubscriptionMember::class, 'subscription_member_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
