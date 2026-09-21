<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationType extends Model
{
    protected $fillable = ['code', 'label', 'color', 'is_active'];

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}
