<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExceptionController extends Controller
{
    protected ExceptionService $exceptionService;

    public function __construct(ExceptionService $exceptionService)
    {
        $this->exceptionService = $exceptionService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ShipmentException::with(['shipment.customer', 'shipment.carrier', 'resolver'])
            ->whereHas('shipment', function ($q) use ($user) {
                $q->forUser($user);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('exception_type')) {
            $query->where('exception_type', $request->exception_type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $exceptions = $query->latest('detected_at')->paginate(10)->withQueryString();

        return view('exceptions.index', compact('exceptions'));
    }

    public function store(Request $request, int $shipmentId)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden. Only administrators and operators can log shipment exceptions.');
        }

        $shipment = Shipment::findOrFail($shipmentId);

        $validated = $request->validate([
            'exception_type' => ['required', 'in:CUSTOMS_HOLD,WEATHER_DELAY,PORT_CONGESTION,DAMAGED,MISROUTED,DOCUMENTATION_ISSUE'],
            'severity' => ['required', 'in:LOW,MEDIUM,HIGH'],
            'description' => ['required', 'string', 'max:1000'],
            'detected_at' => ['nullable', 'date'],
        ]);

        $this->exceptionService->raiseException($shipment, $validated, $user);

        return redirect()->route('shipments.show', $shipment->id)->with('warning', 'Disruption exception raised and timeline flagged.');
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user->canManageShipments()) {
            abort(403, 'Forbidden.');
        }

        $exception = ShipmentException::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,INVESTIGATING,RESOLVED'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->exceptionService->updateExceptionStatus($exception, $validated['status'], $validated['resolution_notes'] ?? null, $user);

        return back()->with('success', "Exception status updated to {$validated['status']}.");
    }
}
