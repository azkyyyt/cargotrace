<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Shipment;
use App\Services\ExcelExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FinanceController extends Controller
{
    public function __construct(
        protected ExcelExportService $excelExportService
    ) {}

    /**
     * Display the Revenue & Financial Ledger dashboard for Admin & Operator.
     */
    public function index(Request $request)
    {
        $matching = $this->buildFilteredQuery($request)->get();
        $paid = $matching->where('payment_status', 'PAID');
        $pending = $matching->where('payment_status', '!=', 'PAID');

        $summary = [
            'total_contracts' => $matching->count(),
            'paid_count' => $paid->count(),
            'paid_commodity_usd' => (float) $paid->sum('commodity_price_usd'),
            'paid_freight_usd' => (float) $paid->sum('freight_charge_usd'),
            'paid_gross_usd' => (float) $paid->sum('gross_amount_usd'),
            'paid_vessel_fee_usd' => (float) $paid->sum('vessel_fee_usd'),
            'paid_net_usd' => (float) $paid->sum('net_revenue_usd'),

            'pending_count' => $pending->count(),
            'pending_gross_usd' => (float) $pending->sum('gross_amount_usd'),
            'pending_vessel_fee_usd' => (float) $pending->sum('vessel_fee_usd'),
            'pending_net_usd' => (float) $pending->sum('net_revenue_usd'),

            'combined_gross_usd' => (float) $matching->sum('gross_amount_usd'),
            'combined_vessel_fee_usd' => (float) $matching->sum('vessel_fee_usd'),
            'combined_net_usd' => (float) $matching->sum('net_revenue_usd'),
            'combined_commodity_usd' => (float) $matching->sum('commodity_price_usd'),
            'combined_freight_usd' => (float) $matching->sum('freight_charge_usd'),
        ];

        $shipments = $this->buildFilteredQuery($request)->paginate(15)->withQueryString();
        $carriers = Carrier::orderBy('name')->get();

        return view('finance.index', compact('shipments', 'summary', 'carriers'));
    }

    /**
     * Download filtered financial ledger as a genuine native OpenXML (.xlsx) workbook.
     */
    public function exportXlsx(Request $request)
    {
        $shipments = $this->buildFilteredQuery($request)->get();
        [$headers, $rows, $totalsRow] = $this->buildExportDataset($shipments);

        $filePath = $this->excelExportService->generateXlsx(
            'CargoTrace Financial Report',
            $headers,
            $rows,
            $totalsRow
        );

        $filename = 'cargotrace_financial_report_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Stream filtered financial ledger as UTF-8 BOM CSV for accounting import.
     */
    public function exportCsv(Request $request)
    {
        $shipments = $this->buildFilteredQuery($request)->get();
        [$headers, $rows, $totalsRow] = $this->buildExportDataset($shipments);

        $filename = 'cargotrace_financial_report_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows, $totalsRow) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($v) => is_float($v) ? number_format($v, 2, '.', '') : $v, $row));
            }

            fputcsv($handle, array_map(fn ($v) => is_float($v) ? number_format($v, 2, '.', '') : $v, $totalsRow));
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Construct a consistent filtered Eloquent query for index & exports.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = Shipment::with(['customer', 'carrier', 'origin', 'destination'])->latest('id');

        if ($request->filled('payment_status') && in_array($request->payment_status, ['PAID', 'PENDING'], true)) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        if ($request->filled('cargo_type')) {
            $query->where('cargo_type', $request->cargo_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query;
    }

    /**
     * Assemble uniform tabular rows and totals for Excel and CSV export.
     *
     * @param Collection<int, Shipment> $shipments
     * @return array{0: array<int, string>, 1: array<int, array<int, mixed>>, 2: array<int, mixed>}
     */
    private function buildExportDataset(Collection $shipments): array
    {
        $headers = [
            'Order Ref',
            'Created At',
            'Customer',
            'Company',
            'Commodity',
            'Volume (MT)',
            'Carrier',
            'Payment Status',
            'Payment Method',
            'Settled At',
            'Commodity Value (USD)',
            'Ocean Freight (USD)',
            'Gross Revenue (USD)',
            'Vessel Charter Fee (USD)',
            'Net Clean Revenue (USD)',
        ];

        $rows = [];
        $sumCommodity = 0.0;
        $sumFreight = 0.0;
        $sumGross = 0.0;
        $sumVesselFee = 0.0;
        $sumNet = 0.0;

        foreach ($shipments as $s) {
            $commodity = (float) $s->commodity_price_usd;
            $freight = (float) $s->freight_charge_usd;
            $gross = (float) $s->gross_amount_usd;
            $vesselFee = (float) $s->vessel_fee_usd;
            $net = (float) $s->net_revenue_usd;

            $sumCommodity += $commodity;
            $sumFreight += $freight;
            $sumGross += $gross;
            $sumVesselFee += $vesselFee;
            $sumNet += $net;

            $rows[] = [
                $s->shipment_number,
                $s->created_at?->format('Y-m-d H:i') ?? '-',
                $s->customer?->name ?? 'N/A',
                $s->customer?->company ?? '-',
                $s->cargo_type,
                number_format((float) $s->weight, 2) . ' MT',
                $s->carrier?->name ?? 'Unassigned',
                $s->payment_status ?? 'PENDING',
                $s->payment_method ?? '-',
                $s->paid_at?->format('Y-m-d H:i') ?? 'Unpaid',
                round($commodity, 2),
                round($freight, 2),
                round($gross, 2),
                round($vesselFee, 2),
                round($net, 2),
            ];
        }

        $totalsRow = [
            'TOTAL (' . $shipments->count() . ' Contracts)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round($sumCommodity, 2),
            round($sumFreight, 2),
            round($sumGross, 2),
            round($sumVesselFee, 2),
            round($sumNet, 2),
        ];

        return [$headers, $rows, $totalsRow];
    }
}
