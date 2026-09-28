<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'product_batch_id', 'type', 'quantity',
        'user_id', 'patient_id', 'prescribing_doctor_id', 'service', 'reason',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescribingDoctor()
    {
        return $this->belongsTo(Doctor::class, 'prescribing_doctor_id');
    }
}
