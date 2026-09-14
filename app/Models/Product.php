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

    /** Affiche "12" plutôt que "12.00", mais garde les décimales si elles comptent (ex: 1.5 kg). */
    public function formattedQuantity(): string
    {
        $q = (float) $this->quantity_on_hand;
        return $q == floor($q) ? (string) (int) $q : rtrim(rtrim(number_format($q, 2, '.', ''), '0'), '.');
    }

    public function nextExpiry(): ?ProductBatch
    {
        return $this->batches()
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->first();
    }

    public function recordMovement(string $type, float $quantity, User $user, array $extra = []): StockMovement
    {
        $batch = null;

        if ($type === 'entry' && ! empty($extra['lot_number'])) {
            $batch = $this->batches()->firstOrNew(['lot_number' => $extra['lot_number']]);
            $batch->expiry_date = $extra['expiry_date'] ?? $batch->expiry_date;
            $batch->quantity = ($batch->quantity ?? 0) + $quantity;
            $batch->save();
        } elseif ($type === 'exit') {
            $batch = $this->batches()->where('quantity', '>', 0)->whereNotNull('expiry_date')
                ->orderBy('expiry_date')->first();
            if ($batch) {
                $batch->decrement('quantity', min($quantity, $batch->quantity));
            }
        }

        $movement = $this->movements()->create([
            'type' => $type,
            'quantity' => $quantity,
            'user_id' => $user->id,
            'product_batch_id' => $batch?->id,
            'patient_id' => $extra['patient_id'] ?? null,
            'service' => $extra['service'] ?? null,
            'reason' => $extra['reason'] ?? null,
        ]);

        $this->increment('quantity_on_hand', $type === 'entry' ? $quantity : -$quantity);

        return $movement;
    }
}
