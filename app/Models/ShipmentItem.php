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
        'grade_specification',
        'quantity',
        'weight',
        'volume',
        'moisture_content_percent',
        'hs_code',
        'package_type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'float',
        'volume' => 'float',
        'moisture_content_percent' => 'float',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
