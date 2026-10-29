<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_CREATED = 'CREATED';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_PICKED_UP = 'PICKED_UP';
    public const STATUS_IN_TRANSIT = 'IN_TRANSIT';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_FLOW = [
        self::STATUS_CREATED => [self::STATUS_BOOKED, self::STATUS_CANCELLED],
        self::STATUS_BOOKED => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED],
        self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT],
        self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [], // Terminal status: strictly non-reversible
        self::STATUS_CANCELLED => [], // Terminal status
    ];

    protected $fillable = [
        'shipment_number',
        'customer_id',
        'carrier_id',
        'origin_id',
        'destination_id',
        'current_location_id',
        'cargo_type',
        'weight',
        'volume',
        'expected_departure',
        'expected_arrival',
        'actual_departure',
        'actual_arrival',
        'estimated_arrival',
        'current_status',
        'delay_status',
        'delay_duration_hours',
        'delay_severity',
        'delay_reason',
        'notes',
    ];

    protected $casts = [
        'weight' => 'float',
        'volume' => 'float',
        'delay_duration_hours' => 'integer',
        'expected_departure' => 'datetime',
        'expected_arrival' => 'datetime',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'estimated_arrival' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function origin()
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    public function destination()
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    public function currentLocation()
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id');
    }

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class, 'shipment_id')->orderBy('event_time', 'asc');
    }

    public function exceptions()
    {
        return $this->hasMany(ShipmentException::class, 'shipment_id')->latest('detected_at');
    }

    public function openExceptions()
    {
        return $this->hasMany(ShipmentException::class, 'shipment_id')->where('status', '!=', 'RESOLVED');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')->where('auditable_type', self::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->current_status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->current_status === $targetStatus) {
            return true;
        }

        $allowedTransitions = self::STATUS_FLOW[$this->current_status] ?? [];
        return in_array($targetStatus, $allowedTransitions, true);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return $query;
        }

        return $query->where('customer_id', $user->id);
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('cargo_type', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        if (!empty($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }

        if (!empty($filters['origin_id'])) {
            $query->where('origin_id', $filters['origin_id']);
        }

        if (!empty($filters['destination_id'])) {
            $query->where('destination_id', $filters['destination_id']);
        }

        if (!empty($filters['delay_status'])) {
            $query->where('delay_status', $filters['delay_status']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('expected_departure', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('expected_arrival', '<=', $filters['end_date']);
        }

        return $query;
    }
}
