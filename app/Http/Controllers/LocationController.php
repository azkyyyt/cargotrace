<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\AuditService;
use App\Services\ExternalApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    protected ExternalApiService $externalApiService;

    public function __construct(ExternalApiService $externalApiService)
    {
        $this->externalApiService = $externalApiService;
    }

    public function index()
    {
        $locations = Location::withCount(['outboundShipments', 'inboundShipments'])->paginate(10);
        return view('locations.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:locations,code'],
            'name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:PORT,AIRPORT,WAREHOUSE,CUSTOMS,HUB'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
        ]);

        // Auto-geocode if coordinates were not provided manually
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $geo = $this->externalApiService->geocode("{$validated['name']}, {$validated['city']}, {$validated['country']}");
            $validated['latitude'] = $geo['latitude'];
            $validated['longitude'] = $geo['longitude'];
        }

        $location = Location::create($validated);
        AuditService::log('LOCATION_CREATED', $location, null, $location->toArray(), $user);

        return redirect()->route('locations.index')->with('success', "Location {$location->name} created successfully.");
    }
}
