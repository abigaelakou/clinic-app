<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCategory extends Model
{
    protected $fillable = ['domain', 'name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
