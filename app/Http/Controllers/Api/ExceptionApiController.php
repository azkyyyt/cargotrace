<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExceptionApiController extends Controller
{
    protected ExceptionService $exceptionService;

    public function __construct(ExceptionService $exceptionService)
    {
        $this->exceptionService = $exceptionService;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        if ($user->isCustomer() && (int) $shipment->customer_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to these exceptions.',
            ], 403);
        }

        $exceptions = $shipment->exceptions()->with(['trackingEvent', 'resolver'])->get();

        return response()->json([
            'success' => true,
            'shipment_number' => $shipment->shipment_number,
            'data' => $exceptions,
        ], 200);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->canManageShipments()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators and operators can log shipment exceptions.',
            ], 403);
        }

        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'exception_type' => ['required', 'in:CUSTOMS_HOLD,WEATHER_DELAY,PORT_CONGESTION,DAMAGED,MISROUTED,DOCUMENTATION_ISSUE'],
            'severity' => ['required', 'in:LOW,MEDIUM,HIGH'],
            'description' => ['required', 'string', 'max:1000'],
            'detected_at' => ['nullable', 'date'],
        ]);

        $exception = $this->exceptionService->raiseException($shipment, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Exception logged and broadcast successfully.',
            'data' => $exception,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->canManageShipments()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators and operators can update exception status.',
            ], 403);
        }

        $exception = ShipmentException::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,INVESTIGATING,RESOLVED'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->exceptionService->updateExceptionStatus(
            $exception,
            $validated['status'],
            $validated['resolution_notes'] ?? null,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Exception status updated successfully.',
            'data' => $updated,
        ], 200);
    }
}
