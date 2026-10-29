<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentException extends Model
{
    use HasFactory;

    public const TYPE_CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    public const TYPE_WEATHER_DELAY = 'WEATHER_DELAY';
    public const TYPE_PORT_CONGESTION = 'PORT_CONGESTION';
    public const TYPE_DAMAGED = 'DAMAGED';
    public const TYPE_MISROUTED = 'MISROUTED';
    public const TYPE_DOCUMENTATION_ISSUE = 'DOCUMENTATION_ISSUE';

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_INVESTIGATING = 'INVESTIGATING';
    public const STATUS_RESOLVED = 'RESOLVED';

    protected $fillable = [
        'shipment_id',
        'tracking_event_id',
        'exception_type',
        'severity',
        'status',
        'description',
        'detected_at',
        'resolved_at',
        'resolution_notes',
        'resolved_by',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function trackingEvent()
    {
        return $this->belongsTo(TrackingEvent::class, 'tracking_event_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
