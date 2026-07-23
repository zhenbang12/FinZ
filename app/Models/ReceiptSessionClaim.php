<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptSessionClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'receipt_item_id',
        'guest_name',
        'amount_paid',
        'is_paid',
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'is_paid' => 'boolean',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function item()
    {
        return $this->belongsTo(ReceiptItem::class, 'receipt_item_id');
    }
}
