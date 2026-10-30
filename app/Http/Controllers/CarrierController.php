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
}
