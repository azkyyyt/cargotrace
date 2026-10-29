<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query scoped by user role
        $shipmentQuery = Shipment::forUser($user);

        // Metric Counts
        $totalShipments = (clone $shipmentQuery)->count();
        $inTransitCount = (clone $shipmentQuery)->where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $deliveredCount = (clone $shipmentQuery)->where('current_status', Shipment::STATUS_DELIVERED)->count();
        $delayedCount = (clone $shipmentQuery)->where('delay_status', 'DELAYED')->count();
        $bookedCount = (clone $shipmentQuery)->where('current_status', Shipment::STATUS_BOOKED)->count();
        $pickedUpCount = (clone $shipmentQuery)->where('current_status', Shipment::STATUS_PICKED_UP)->count();

        // Exceptions Query
        $exceptionQuery = ShipmentException::whereHas('shipment', function ($q) use ($user) {
            $q->forUser($user);
        });
        $openExceptionsCount = (clone $exceptionQuery)->where('status', '!=', ShipmentException::STATUS_RESOLVED)->count();
        $resolvedExceptionsCount = (clone $exceptionQuery)->where('status', ShipmentException::STATUS_RESOLVED)->count();

        // Operational Rates / KPIs
        $onTimeDeliveryRate = $deliveredCount > 0
            ? round(((clone $shipmentQuery)->where('current_status', Shipment::STATUS_DELIVERED)->where('delay_status', 'ON_TIME')->count() / $deliveredCount) * 100, 1)
            : 100.0;

        $delayRate = $totalShipments > 0
            ? round(($delayedCount / $totalShipments) * 100, 1)
            : 0.0;

        $avgDelayDuration = (clone $shipmentQuery)->where('delay_status', 'DELAYED')->avg('delay_duration_hours') ?? 0;
        $avgDelayDuration = round($avgDelayDuration, 1);

        // Chart Data 1: Shipments by Status
        $statuses = [
            Shipment::STATUS_CREATED,
            Shipment::STATUS_BOOKED,
            Shipment::STATUS_PICKED_UP,
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_CANCELLED,
        ];
        $statusCounts = [];
        foreach ($statuses as $st) {
            $statusCounts[$st] = (clone $shipmentQuery)->where('current_status', $st)->count();
        }

        // Chart Data 2: Shipments by Carrier
        $carriers = Carrier::withCount(['shipments' => function ($q) use ($user) {
            $q->forUser($user);
        }])->get();
        $carrierLabels = $carriers->pluck('name')->toArray();
        $carrierData = $carriers->pluck('shipments_count')->toArray();

        // Chart Data 3: Exceptions by Type
        $exceptionTypes = [
            'CUSTOMS_HOLD',
            'WEATHER_DELAY',
            'PORT_CONGESTION',
            'DAMAGED',
            'MISROUTED',
            'DOCUMENTATION_ISSUE',
        ];
        $exceptionTypeCounts = [];
        foreach ($exceptionTypes as $type) {
            $exceptionTypeCounts[$type] = (clone $exceptionQuery)->where('exception_type', $type)->count();
        }

        // Recent Shipments
        $recentShipments = (clone $shipmentQuery)
            ->with(['carrier', 'origin', 'destination', 'customer'])
            ->latest()
            ->take(6)
            ->get();

        // Recent Tracking Activity
        $recentEvents = TrackingEvent::whereHas('shipment', function ($q) use ($user) {
            $q->forUser($user);
        })->with(['shipment', 'location'])->latest('event_time')->take(5)->get();

        // Active Urgent Exceptions
        $activeExceptions = (clone $exceptionQuery)
            ->with(['shipment.customer'])
            ->where('status', '!=', ShipmentException::STATUS_RESOLVED)
            ->orderByRaw("FIELD(severity, 'HIGH', 'MEDIUM', 'LOW')")
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalShipments',
            'inTransitCount',
            'deliveredCount',
            'delayedCount',
            'bookedCount',
            'pickedUpCount',
            'openExceptionsCount',
            'resolvedExceptionsCount',
            'onTimeDeliveryRate',
            'delayRate',
            'avgDelayDuration',
            'statusCounts',
            'carrierLabels',
            'carrierData',
            'exceptionTypeCounts',
            'recentShipments',
            'recentEvents',
            'activeExceptions'
        ));
    }
}
