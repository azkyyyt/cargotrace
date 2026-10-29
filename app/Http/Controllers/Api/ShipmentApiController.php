<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShipmentApiController extends Controller
{
    protected ShipmentService $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination', 'currentLocation'])
            ->forUser($user)
            ->filter($request->only([
                'search', 'status', 'carrier_id', 'origin_id', 'destination_id', 'delay_status', 'start_date', 'end_date'
            ]));

        $sortField = in_array($request->sort_by, ['id', 'shipment_number', 'expected_departure', 'expected_arrival', 'current_status'], true)
            ? $request->sort_by
            : 'id';
        $sortOrder = $request->order === 'asc' ? 'asc' : 'desc';

        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);
        $shipments = $query->orderBy($sortField, $sortOrder)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shipments->items(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->canManageShipments()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators and operators can create shipments.',
            ], 403);
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
            'current_status' => ['nullable', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_description' => ['required_with:items', 'string', 'max:200'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.volume' => ['nullable', 'numeric'],
            'items.*.package_type' => ['nullable', 'string'],
        ]);

        $shipment = $this->shipmentService->createShipment($validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully.',
            'data' => $shipment,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $shipment = Shipment::with([
            'customer', 'carrier', 'origin', 'destination', 'currentLocation',
            'items', 'trackingEvents.location', 'exceptions.resolver'
        ])->findOrFail($id);

        $user = $request->user();
        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You can only view your own shipments.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $shipment,
        ], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->canManageShipments()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators and operators can update shipments.',
            ], 403);
        }

        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'carrier_id' => ['sometimes', 'exists:carriers,id'],
            'origin_id' => ['sometimes', 'exists:locations,id'],
            'destination_id' => ['sometimes', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'string', 'max:100'],
            'weight' => ['sometimes', 'numeric', 'min:0.1'],
            'volume' => ['sometimes', 'numeric', 'min:0.01'],
            'expected_departure' => ['sometimes', 'date'],
            'expected_arrival' => ['sometimes', 'date'],
            'estimated_arrival' => ['sometimes', 'date'],
            'current_status' => ['sometimes', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'notes' => ['nullable', 'string'],
        ]);

        $updated = $this->shipmentService->updateShipment($shipment, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully.',
            'data' => $updated,
        ], 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can delete shipments.',
            ], 403);
        }

        $shipment = Shipment::findOrFail($id);
        $this->shipmentService->deleteShipment($shipment, $user);

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully.',
        ], 200);
    }
}
