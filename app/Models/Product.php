<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'stock_category_id', 'name', 'unit', 'galenic_form', 'dosage',
        'supplier', 'purchase_price', 'quantity_on_hand',
        'alert_threshold', 'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(StockCategory::class, 'stock_category_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeBelowThreshold($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'alert_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_on_hand', '<=', 0);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereHas('batches', fn ($b) => $b->whereBetween('expiry_date', [now(), now()->addDays($days)]));
    }

    public function status(): string
    {
        if ($this->quantity_on_hand <= 0) return 'rupture';
        if ($this->quantity_on_hand <= $this->alert_threshold) return 'alerte';
        return 'ok';
    }

    public function recordMovement(string $type, float $quantity, User $user, array $extra = []): StockMovement
    {
        $movement = $this->movements()->create(array_merge([
            'type' => $type,
            'quantity' => $quantity,
            'user_id' => $user->id,
        ], $extra));

        $this->increment('quantity_on_hand', $type === 'entry' ? $quantity : -$quantity);

        return $movement;
    }
}
