<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ExternalApiService;
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

        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.show', compact(
            'shipment',
            'originWeather',
            'destinationWeather',
            'mapPoints',
            'carriers',
            'locations'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden. Only administrators and operators can create new shipments.');
        }

        $customers = User::where('role', 'customer')->get();
        $carriers = Carrier::where('is_active', true)->get();
        $locations = Location::all();

        return view('shipments.create', compact('customers', 'carriers', 'locations'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden. Only administrators and operators can create shipments.');
        }

        $validated = $request->validate([
            'shipment_number' => ['nullable', 'string', 'unique:shipments,shipment_number'],
            'customer_id' => ['required', 'exists:users,id'],
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
        ]);

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
