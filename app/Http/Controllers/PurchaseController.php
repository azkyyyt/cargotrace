<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public const COMMODITY_CATALOG = [
        'Rice' => [
            'name' => 'Premium Jasmine White Rice',
            'grade' => 'Export Grade A, Broken 5% Max',
            'hs_code' => '1006.30.90',
            'unit_price' => 580.00,
            'origin_hint' => 'Laem Chabang / Southeast Asia',
        ],
        'Wheat' => [
            'name' => 'Australian Prime Hard Wheat',
            'grade' => 'Milling Grade 1, Protein 13.0% Min',
            'hs_code' => '1001.99.90',
            'unit_price' => 275.00,
            'origin_hint' => 'Fremantle / Western Australia',
        ],
        'Corn' => [
            'name' => 'Yellow Dent Corn #2',
            'grade' => 'US/BR No. 2 Yellow Corn, Moisture 14%',
            'hs_code' => '1005.90.90',
            'unit_price' => 215.00,
            'origin_hint' => 'Santos / Americas',
        ],
        'Soybeans' => [
            'name' => 'Non-GMO Food-Grade Soybeans',
            'grade' => 'Export Grade #1, Protein 38% Min',
            'hs_code' => '1201.90.00',
            'unit_price' => 445.00,
            'origin_hint' => 'South Louisiana / Qingdao',
        ],
    ];

    public const FREIGHT_RATE_PER_MT = 45.00;
    public const VESSEL_FEE_PER_MT = 28.00;

    public function __construct(
        protected ShipmentService $shipmentService
    ) {}

    /**
     * Display the customer commodity procurement catalog & order composer.
     */
    public function index()
    {
        $user = Auth::user();
        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $ports = Location::orderBy('country')->orderBy('name')->get();

        $pendingOrders = Shipment::with(['origin', 'destination', 'carrier'])
            ->where('customer_id', $user->id)
            ->where('payment_status', '!=', 'PAID')
            ->latest()
            ->take(5)
            ->get();

        return view('shop.index', [
            'catalog' => self::COMMODITY_CATALOG,
            'freightRate' => self::FREIGHT_RATE_PER_MT,
            'carriers' => $carriers,
            'ports' => $ports,
            'pendingOrders' => $pendingOrders,
        ]);
    }

    /**
     * Register a new customer commodity order with PENDING payment status,
     * then redirect to the Payment Gateway checkout.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cargo_type' => 'required|in:Rice,Wheat,Corn,Soybeans',
            'weight' => 'required|numeric|min:10|max:250000',
            'carrier_id' => 'required|exists:carriers,id',
            'origin_id' => 'required|exists:locations,id',
            'destination_id' => 'required|exists:locations,id|different:origin_id',
            'grade_specification' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:QRIS_REALTIME,VIRTUAL_ACCOUNT,LETTER_OF_CREDIT',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shipment = $this->shipmentService->createGrainBooking($validated, Auth::user());

        return redirect()
            ->route('shipments.payment', $shipment->id)
            ->with('success', "Purchase order {$shipment->shipment_number} created. Complete payment below to unlock vessel tracking.");
    }

    /**
     * Show the Payment Gateway checkout page for a customer's unpaid order.
     */
    public function showPayment(int $id)
    {
        $shipment = Shipment::with(['customer', 'carrier', 'origin', 'destination', 'items'])
            ->findOrFail($id);

        if ((int) $shipment->customer_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized. You can only pay for your own purchase orders.');
        }

        if ($shipment->isPaid()) {
            return redirect()
                ->route('shipments.show', $shipment->id)
                ->with('success', "Payment for {$shipment->shipment_number} is already settled. Live vessel tracking is active.");
        }

        return view('shop.payment', [
            'shipment' => $shipment,
        ]);
    }

    /**
     * Settle payment via the simulated Payment Gateway and unlock live vessel tracking.
     */
    public function processPayment(Request $request, int $id)
    {
        $shipment = Shipment::findOrFail($id);

        if ((int) $shipment->customer_id !== (int) Auth::id()) {
            abort(403, 'Unauthorized. You can only settle your own purchase orders.');
        }

        if ($shipment->isPaid()) {
            return redirect()
                ->route('shipments.show', $shipment->id)
                ->with('success', "Order {$shipment->shipment_number} is already settled.");
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:QRIS_REALTIME,VIRTUAL_ACCOUNT,LETTER_OF_CREDIT',
        ]);

        $settled = $this->shipmentService->processSimulatedPayment(
            $shipment,
            $validated['payment_method'],
            Auth::user()
        );

        return redirect()
            ->route('shipments.show', $settled->id)
            ->with('success', "Payment settled ({$settled->payment_reference}). Vessel allocation confirmed and live maritime tracking is now unlocked.");
    }
}
