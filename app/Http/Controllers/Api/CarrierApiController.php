<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrierApiController extends Controller
{
    public function index(): JsonResponse
    {
        $carriers = Carrier::where('is_active', true)->get();
        return response()->json([
            'success' => true,
            'data' => $carriers,
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can add carriers.',
            ], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:carriers,code'],
            'name' => ['required', 'string', 'max:100'],
            'transport_mode' => ['required', 'in:SEA,AIR,ROAD,RAIL'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $carrier = Carrier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Carrier created successfully.',
            'data' => $carrier,
        ], 201);
    }
}
