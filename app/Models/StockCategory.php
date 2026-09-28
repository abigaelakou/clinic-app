<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCategory extends Model
{
    protected $fillable = ['domain', 'name', 'default_alert_threshold'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
