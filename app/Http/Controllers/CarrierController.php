<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarrierController extends Controller
{
    public function index()
    {
        $carriers = Carrier::withCount('shipments')->paginate(10);
        return view('carriers.index', compact('carriers'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:carriers,code'],
            'name' => ['required', 'string', 'max:100'],
            'transport_mode' => ['required', 'in:SEA,AIR,ROAD,RAIL'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
        ]);

        $carrier = Carrier::create($validated);
        AuditService::log('CARRIER_CREATED', $carrier, null, $carrier->toArray(), $user);

        return redirect()->route('carriers.index')->with('success', "Carrier {$carrier->name} created.");
    }

    public function toggle(int $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $carrier = Carrier::findOrFail($id);
        $carrier->is_active = !$carrier->is_active;
        $carrier->save();

        AuditService::log('CARRIER_STATUS_TOGGLED', $carrier, null, ['is_active' => $carrier->is_active], $user);

        return back()->with('success', "Carrier status updated.");
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $carrier = Carrier::findOrFail($id);
        $old = $carrier->toArray();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', "unique:carriers,code,{$carrier->id}"],
            'name' => ['required', 'string', 'max:100'],
            'transport_mode' => ['required', 'in:SEA,AIR,ROAD,RAIL'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
        ]);

        $carrier->update($validated);
        AuditService::log('CARRIER_UPDATED', $carrier, $old, $carrier->toArray(), $user);

        return redirect()->route('carriers.index')->with('success', "Carrier {$carrier->name} updated.");
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $carrier = Carrier::withCount('shipments')->findOrFail($id);
        if ($carrier->shipments_count > 0) {
            return back()->with('error', "Cannot delete {$carrier->name} because {$carrier->shipments_count} shipment(s) use it. Deactivate it instead.");
        }

        $old = $carrier->toArray();
        $carrier->delete();
        AuditService::log('CARRIER_DELETED', $carrier, $old, null, $user);

        return redirect()->route('carriers.index')->with('success', 'Carrier deleted.');
    }
}
