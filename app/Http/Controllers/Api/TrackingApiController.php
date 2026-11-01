<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingApiController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this tracking data.',
            ], 403);
        }

        $events = $this->trackingService->getTimeline($shipment);

        return response()->json([
            'success' => true,
            'shipment_number' => $shipment->shipment_number,
            'current_status' => $shipment->current_status,
            'data' => $events,
        ], 200);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->canManageShipments()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators and operators can record tracking events.',
            ], 403);
        }

        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'event_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['required', 'string', 'max:1000'],
            'event_time' => ['nullable', 'date'],
        ]);

        $event = $this->trackingService->addEvent($shipment, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Tracking event registered successfully.',
            'data' => $event,
        ], 201);
    }
}
