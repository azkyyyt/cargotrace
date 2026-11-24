<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ExternalApiService;
use App\Services\SeaRouteService;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentController extends Controller
{
    protected ShipmentService $shipmentService;
    protected ExternalApiService $externalApiService;

    public function __construct(ShipmentService $shipmentService, ExternalApiService $externalApiService)
    {
        $this->shipmentService = $shipmentService;
        $this->externalApiService = $externalApiService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination', 'currentLocation'])
            ->forUser($user)
            ->filter($request->only([
                'search', 'status', 'carrier_id', 'origin_id', 'destination_id', 'delay_status', 'start_date', 'end_date'
            ]));

        $sortField = in_array($request->sort_by, ['id', 'shipment_number', 'expected_departure', 'expected_arrival', 'current_status'], true)
            ? $request->sort_by
            : 'id';
        $sortOrder = $request->order === 'asc' ? 'asc' : 'desc';

        $shipments = $query->orderBy($sortField, $sortOrder)->paginate(10)->withQueryString();

        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.index', compact('shipments', 'carriers', 'locations'));
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $shipment = Shipment::with([
            'customer', 'carrier', 'origin', 'destination', 'currentLocation',
            'items', 'trackingEvents.location', 'trackingEvents.creator',
            'exceptions.resolver'
        ])->findOrFail($id);

        // Authorization check: Customer can only view their own shipments
        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            abort(403, 'Unauthorized. You can only view your own shipments.');
        }

        // Fetch contextual live weather from external API for Origin & Destination
        $originWeather = null;
        $destinationWeather = null;

        if ($shipment->origin) {
            $originWeather = $this->externalApiService->getWeather(
                $shipment->origin->latitude,
                $shipment->origin->longitude
            );
        }

        if ($shipment->destination) {
            $destinationWeather = $this->externalApiService->getWeather(
                $shipment->destination->latitude,
                $shipment->destination->longitude
            );
        }

        // Build Leaflet Map Waypoints
        $mapPoints = [];
        if ($shipment->origin) {
            $mapPoints[] = [
                'type' => 'origin',
                'name' => 'Origin: ' . $shipment->origin->name,
                'city' => $shipment->origin->city . ', ' . $shipment->origin->country,
                'lat' => (float) $shipment->origin->latitude,
                'lng' => (float) $shipment->origin->longitude,
                'time' => $shipment->actual_departure ? $shipment->actual_departure->format('M d, Y H:i') : 'Scheduled: ' . $shipment->expected_departure->format('M d, Y'),
                'status' => 'ORIGIN',
            ];
        }

        foreach ($shipment->trackingEvents as $event) {
            if ($event->latitude && $event->longitude) {
                $mapPoints[] = [
                    'type' => $event->event_type === 'EXCEPTION_FLAG' ? 'exception' : 'checkpoint',
                    'name' => $event->location_name,
                    'city' => $event->description,
                    'lat' => (float) $event->latitude,
                    'lng' => (float) $event->longitude,
                    'time' => $event->event_time->format('M d, Y H:i'),
                    'status' => $event->status,
                ];
            }
        }

        if ($shipment->destination) {
            $mapPoints[] = [
                'type' => 'destination',
                'name' => 'Destination: ' . $shipment->destination->name,
                'city' => $shipment->destination->city . ', ' . $shipment->destination->country,
                'lat' => (float) $shipment->destination->latitude,
                'lng' => (float) $shipment->destination->longitude,
                'time' => $shipment->actual_arrival ? 'Delivered: ' . $shipment->actual_arrival->format('M d, Y H:i') : 'Expected: ' . $shipment->expected_arrival->format('M d, Y'),
                'status' => 'DESTINATION',
            ];
        }

        // Predefined Realistic Corridor Polyline
        $originCode = $shipment->origin?->code ?? '';
        $destCode = $shipment->destination?->code ?? '';
        $corridorPath = SeaRouteService::getRoute($originCode, $destCode);

        // Current active vessel position & bearing
        $latestEvent = $shipment->trackingEvents->whereNotNull('latitude')->whereNotNull('longitude')->last();
        $currentLat = $latestEvent ? (float) $latestEvent->latitude : (float) ($shipment->origin?->latitude ?? 0);
        $currentLng = $latestEvent ? (float) $latestEvent->longitude : (float) ($shipment->origin?->longitude ?? 0);
        $currentLocationName = $latestEvent ? $latestEvent->location_name : ($shipment->origin?->name ?? 'Origin Hub');

        $vesselHeading = 90.0;
        $voyageProgress = 0.0;

        if (!empty($corridorPath) && count($corridorPath) > 1) {
            if ($shipment->current_status === Shipment::STATUS_IN_TRANSIT) {
                $dep = $shipment->actual_departure ?? $shipment->expected_departure;
                $arr = $shipment->estimated_arrival ?? $shipment->expected_arrival;
                $totalSec = max(1, $arr->diffInSeconds($dep));
                $elapsedSec = max(0, now()->diffInSeconds($dep, false));
                $timeRatio = $elapsedSec / $totalSec;

                // Check if latest tracking event is at origin
                $isAtOrigin = ($shipment->origin && abs($currentLat - $shipment->origin->latitude) < 0.2 && abs($currentLng - $shipment->origin->longitude) < 0.2);

                if ($isAtOrigin) {
                    // Underway along ocean corridor
                    $voyageProgress = max(0.25, min(0.85, $timeRatio));
                    $underway = SeaRouteService::interpolatePositionAlongCorridor($corridorPath, $voyageProgress);
                    $currentLat = $underway['lat'];
                    $currentLng = $underway['lng'];
                    $currentLocationName = $underway['location_name'];
                    $vesselHeading = $underway['heading'];
                } else {
                    // Calculate closest index along corridor
                    $closestDist = PHP_FLOAT_MAX;
                    $closestIdx = 0;
                    foreach ($corridorPath as $idx => $wpt) {
                        $d = hypot($wpt[0] - $currentLat, $wpt[1] - $currentLng);
                        if ($d < $closestDist) {
                            $closestDist = $d;
                            $closestIdx = $idx;
                        }
                    }
                    $voyageProgress = min(0.95, max(0.05, $closestIdx / (count($corridorPath) - 1)));
                    $nextIdx = min(count($corridorPath) - 1, $closestIdx + 1);
                    $vesselHeading = SeaRouteService::calculateBearing(
                        $currentLat,
                        $currentLng,
                        $corridorPath[$nextIdx][0],
                        $corridorPath[$nextIdx][1]
                    );
                }
            } elseif ($shipment->current_status === Shipment::STATUS_DELIVERED) {
                $voyageProgress = 1.0;
                $lastPt = $corridorPath[count($corridorPath) - 1];
                $currentLat = (float) $lastPt[0];
                $currentLng = (float) $lastPt[1];
                $currentLocationName = $shipment->destination?->name ?? ($lastPt[2] ?? 'Destination');
                $vesselHeading = 90.0;
            }
        }

        $vesselInfo = [
            'lat' => $currentLat,
            'lng' => $currentLng,
            'name' => $currentLocationName,
            'heading' => round($vesselHeading, 1),
            'status' => $shipment->current_status,
            'transport_mode' => $shipment->carrier?->transport_mode ?? 'SEA',
            'carrier_name' => $shipment->carrier?->name ?? 'Carrier',
            'has_exception' => $shipment->exceptions->where('status', '!=', 'RESOLVED')->count() > 0,
            'progress' => round($voyageProgress, 4),
        ];

        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.show', compact(
            'shipment',
            'originWeather',
            'destinationWeather',
            'mapPoints',
            'corridorPath',
            'vesselInfo',
            'carriers',
            'locations'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        // Customers only manage/order for their own account; operators and admins can select any customer
        if ($user->isCustomer()) {
            $customers = collect([$user]);
        } else {
            $customers = User::where('role', 'customer')->get();
        }

        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.create', compact('customers', 'carriers', 'locations'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'shipment_number' => ['nullable', 'string', 'unique:shipments,shipment_number'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_id' => ['required', 'exists:locations,id'],
            'destination_id' => ['required', 'exists:locations,id', 'different:origin_id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'volume' => ['required', 'numeric', 'min:0.01'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'item_description' => ['nullable', 'string'],
            'item_quantity' => ['nullable', 'integer', 'min:1'],
        ];

        // Only admins/operators select customer_id; for customers, it is strictly their own account
        if ($user->canManageShipments()) {
            $rules['customer_id'] = ['required', 'exists:users,id'];
        }

        $validated = $request->validate($rules);

        if ($user->isCustomer()) {
            $validated['customer_id'] = $user->id;
        }

        if (!empty($validated['item_description'])) {
            $validated['items'] = [
                [
                    'item_description' => $validated['item_description'],
                    'quantity' => $validated['item_quantity'] ?? 1,
                    'weight' => $validated['weight'],
                    'volume' => $validated['volume'],
                ]
            ];
        }

        $shipment = $this->shipmentService->createShipment($validated, $user);

        return redirect()->route('shipments.show', $shipment->id)->with('success', "Shipment {$shipment->shipment_number} created successfully.");
    }

    public function edit(int $id)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden. Only administrators and operators can edit shipments.');
        }

        $shipment = Shipment::findOrFail($id);
        $customers = User::where('role', 'customer')->get();
        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.edit', compact('shipment', 'customers', 'carriers', 'locations'));
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden.');
        }

        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_id' => ['required', 'exists:locations,id'],
            'destination_id' => ['required', 'exists:locations,id', 'different:origin_id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'volume' => ['required', 'numeric', 'min:0.01'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->shipmentService->updateShipment($shipment, $validated, $user);

        return redirect()->route('shipments.show', $shipment->id)->with('success', 'Shipment updated successfully.');
    }

    public function transition(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden.');
        }

        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'target_status' => ['required', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->shipmentService->transitionStatus($shipment, $validated['target_status'], $user, $validated['reason'] ?? null);

        return redirect()->route('shipments.show', $shipment->id)->with('success', "Shipment status transitioned to {$validated['target_status']}.");
    }

    /**
     * Customer Grain Cargo Booking (Wheat, Corn, Soybeans, Rice)
     */
    public function bookGrain(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'cargo_type' => ['required', 'in:Wheat,Corn,Soybeans,Rice'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_id' => ['required', 'exists:locations,id'],
            'destination_id' => ['required', 'exists:locations,id', 'different:origin_id'],
            'weight' => ['required', 'numeric', 'min:100'], // Tonase
            'grade_specification' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:QRIS_REALTIME,VIRTUAL_ACCOUNT,LETTER_OF_CREDIT,CREDIT_CARD'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shipment = $this->shipmentService->createGrainBooking($validated, $user);

        return redirect()->route('shipments.show', $shipment->id)->with('success', "Grain consignment {$shipment->shipment_number} ordered! Proceed with simulated payment.");
    }

    /**
     * Real-Time Simulated Payment Gateway Processor
     */
    public function simulatePayment(Request $request, int $id)
    {
        $user = Auth::user();
        $shipment = Shipment::findOrFail($id);

        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:QRIS_REALTIME,VIRTUAL_ACCOUNT,LETTER_OF_CREDIT,CREDIT_CARD'],
        ]);

        $this->shipmentService->processSimulatedPayment($shipment, $validated['payment_method'], $user);

        return redirect()->route('shipments.show', $shipment->id)->with('success', "Real-Time Payment Verified! Shipment {$shipment->shipment_number} is now BOOKED and allocated to the ocean corridor.");
    }

    /**
     * Live Weather Evaluation Trigger:
     * Reads current oceanic conditions and automatically computes delay hours + flags WEATHER_DELAY if adverse.
     */
    public function checkWeatherImpact(int $id)
    {
        $user = Auth::user();
        $shipment = Shipment::with(['origin', 'destination', 'trackingEvents'])->findOrFail($id);

        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            abort(403, 'Unauthorized.');
        }

        // Get latest coordinates or origin coordinates
        $latestEvent = $shipment->trackingEvents->whereNotNull('latitude')->whereNotNull('longitude')->last();
        $lat = $latestEvent ? (float) $latestEvent->latitude : (float) ($shipment->origin?->latitude ?? 0);
        $lon = $latestEvent ? (float) $latestEvent->longitude : (float) ($shipment->origin?->longitude ?? 0);

        $weatherImpact = $this->externalApiService->calculateWeatherImpactHours($lat, $lon);

        if ($weatherImpact['is_adverse']) {
            $newEstimatedArrival = $shipment->expected_arrival->copy()->addHours($weatherImpact['delay_hours']);
            $this->shipmentService->updateShipment($shipment, [
                'estimated_arrival' => $newEstimatedArrival,
            ], $user);

            // Create exception if not already open
            $hasWeatherEx = $shipment->exceptions()->where('exception_type', 'WEATHER_DELAY')->where('status', '!=', 'RESOLVED')->exists();
            if (!$hasWeatherEx) {
                $te = $shipment->trackingEvents()->create([
                    'event_type' => 'EXCEPTION_FLAG',
                    'status' => $shipment->current_status,
                    'location_name' => "Oceanic Zone [{$lat}, {$lon}]",
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'description' => "[AUTOMATED LIVE WEATHER] {$weatherImpact['reason']}",
                    'event_time' => now(),
                    'created_by' => $user->id,
                ]);

                $shipment->exceptions()->create([
                    'tracking_event_id' => $te->id,
                    'exception_type' => 'WEATHER_DELAY',
                    'severity' => $weatherImpact['severity'],
                    'status' => 'OPEN',
                    'description' => $weatherImpact['reason'],
                    'detected_at' => now(),
                ]);
            }

            return redirect()->route('shipments.show', $shipment->id)->with('warning', "Live Weather Alert: Wind {$weatherImpact['weather']['windspeed']} km/h. Automated Delay of +{$weatherImpact['delay_hours']}h applied to ETA.");
        }

        return redirect()->route('shipments.show', $shipment->id)->with('info', "Live Weather Check: Wind {$weatherImpact['weather']['windspeed']} km/h. Conditions favorable for bulk grain transit (0h delay).");
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Forbidden. Only administrators can delete shipments.');
        }

        $shipment = Shipment::findOrFail($id);
        $number = $shipment->shipment_number;
        $this->shipmentService->deleteShipment($shipment, $user);

        return redirect()->route('shipments.index')->with('success', "Shipment {$number} deleted successfully.");
    }
}
