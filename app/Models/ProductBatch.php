<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = ['product_id', 'lot_number', 'expiry_date', 'quantity'];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
