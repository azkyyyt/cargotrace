<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingEventController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function store(Request $request, int $shipmentId)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden. Only administrators and operators can record tracking events.');
        }

        $shipment = Shipment::findOrFail($shipmentId);

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['required', 'string', 'max:1000'],
            'event_time' => ['nullable', 'date'],
        ]);

        $this->trackingService->addEvent($shipment, $validated, $user);

        return redirect()->route('shipments.show', $shipment->id)->with('success', 'New checkpoint event recorded in timeline.');
    }
}
