<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'item_description',
        'quantity',
        'weight',
        'volume',
        'hs_code',
        'package_type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'float',
        'volume' => 'float',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
