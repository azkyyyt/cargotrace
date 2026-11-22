<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Services\SeaRouteService;
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

        // Active Global Fleet Waypoints & Transit Corridors for Real-Time Map
        $activeFleetShipments = (clone $shipmentQuery)
            ->with(['carrier', 'origin', 'destination', 'currentLocation', 'trackingEvents.location', 'exceptions'])
            ->get()
            ->map(function ($s) {
                // Determine current coordinates
                $lat = $s->origin?->latitude ?? 0;
                $lng = $s->origin?->longitude ?? 0;
                $locationName = $s->origin?->name ?? 'Origin Hub';
                
                $latestEvent = $s->trackingEvents->whereNotNull('latitude')->whereNotNull('longitude')->last();
                if ($latestEvent) {
                    $lat = (float) $latestEvent->latitude;
                    $lng = (float) $latestEvent->longitude;
                    $locationName = $latestEvent->location_name;
                } elseif ($s->currentLocation) {
                    $lat = (float) $s->currentLocation->latitude;
                    $lng = (float) $s->currentLocation->longitude;
                    $locationName = $s->currentLocation->name;
                }

                // Collect trajectory waypoints
                $corridor = [];
                $originCode = $s->origin?->code ?? '';
                $destCode = $s->destination?->code ?? '';
                $predefinedRoute = SeaRouteService::getRoute($originCode, $destCode);

                if (!empty($predefinedRoute)) {
                    foreach ($predefinedRoute as $pt) {
                        $corridor[] = [$pt[0], $pt[1]];
                    }
                } else {
                    if ($s->origin) {
                        $corridor[] = [(float) $s->origin->latitude, (float) $s->origin->longitude];
                    }
                    foreach ($s->trackingEvents as $te) {
                        if ($te->latitude && $te->longitude) {
                            $corridor[] = [(float) $te->latitude, (float) $te->longitude];
                        }
                    }
                    if ($s->destination) {
                        $corridor[] = [(float) $s->destination->latitude, (float) $s->destination->longitude];
                    }
                }

                // Compute heading bearing towards next waypoint & voyage progress if in transit
                $heading = 90; // Default East
                $voyageProgress = 0.0;

                if (!empty($corridor) && count($corridor) > 1) {
                    if ($s->current_status === Shipment::STATUS_IN_TRANSIT) {
                        $isAtOrigin = ($s->origin && abs($lat - (float) $s->origin->latitude) < 0.25 && abs($lng - (float) $s->origin->longitude) < 0.25);
                        if ($isAtOrigin) {
                            $voyageProgress = 0.42; // default underway progress on high seas
                            $underway = SeaRouteService::interpolatePositionAlongCorridor($corridor, $voyageProgress);
                            $lat = $underway['lat'];
                            $lng = $underway['lng'];
                            $locationName = $underway['location_name'];
                            $heading = $underway['heading'];
                        } else {
                            $closestIdx = 0;
                            $minDist = PHP_FLOAT_MAX;
                            foreach ($corridor as $idx => $wpt) {
                                $d = hypot($wpt[0] - $lat, $wpt[1] - $lng);
                                if ($d < $minDist) {
                                    $minDist = $d;
                                    $closestIdx = $idx;
                                }
                            }
                            $voyageProgress = min(0.95, max(0.05, $closestIdx / (count($corridor) - 1)));
                            $nextIdx = min(count($corridor) - 1, $closestIdx + 1);
                            if ($closestIdx !== $nextIdx) {
                                $heading = SeaRouteService::calculateBearing(
                                    $lat,
                                    $lng,
                                    $corridor[$nextIdx][0],
                                    $corridor[$nextIdx][1]
                                );
                            }
                        }
                    } elseif ($s->current_status === Shipment::STATUS_DELIVERED) {
                        $voyageProgress = 1.0;
                    }
                }

                return [
                    'id' => $s->id,
                    'number' => $s->shipment_number,
                    'status' => $s->current_status,
                    'carrier' => $s->carrier?->name ?? 'Carrier',
                    'transport_mode' => $s->carrier?->transport_mode ?? 'SEA',
                    'cargo' => $s->cargo_type,
                    'origin' => $s->origin?->city . ' (' . $s->origin?->country . ')',
                    'destination' => $s->destination?->city . ' (' . $s->destination?->country . ')',
                    'current_location' => $locationName,
                    'lat' => $lat,
                    'lng' => $lng,
                    'heading' => round($heading, 1),
                    'progress' => round($voyageProgress * 100, 1),
                    'delay_status' => $s->delay_status,
                    'delay_hours' => $s->delay_duration_hours,
                    'delay_severity' => $s->delay_severity,
                    'has_exception' => $s->exceptions->where('status', '!=', 'RESOLVED')->count() > 0,
                    'corridor' => $corridor,
                ];
            });

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
            'activeExceptions',
            'activeFleetShipments'
        ));
    }
}
