<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'merchant_name',
        'subtotal',
        'tax_amount',
        'service_charge_amount',
        'discount_amount',
        'total_amount',
        'raw_ocr_data',
        'status',
        'share_token',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'raw_ocr_data' => 'array',
    ];

    public function getImagePathAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        if (str_starts_with($value, 'receipts/')) {
            return '/storage/' . $value;
        }

        return '/' . ltrim($value, '/');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function sessionClaims(): HasMany
    {
        return $this->hasMany(ReceiptSessionClaim::class);
    }
}
