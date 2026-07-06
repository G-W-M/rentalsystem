<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV/PDF export for Payments and Maintenance. Visible to BOTH admin
 * (unscoped, all data) and landlord (scoped to their own properties).
 * Same controller serves both roles — scope is applied based on the
 * authenticated user's role, exactly like the *Index methods elsewhere.
 */
class ExportController extends Controller
{
    /**
     * GET /api/{role}/payments/export/csv
     */
    public function paymentsCsv(Request $request): StreamedResponse
    {
        $query = Payment::with('tenant.user:id,full_name', 'unit:id,unit_number,property_id');
        $this->scopePaymentsToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->latest('due_date')->get();

        $filename = 'payments_export_' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Tenant', 'Unit', 'Amount', 'Due Date', 'Status', 'Transaction ID', 'Receipt', 'Verified At']);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->tenant && $p->tenant->user ? $p->tenant->user->full_name : '-',
                    $p->unit ? $p->unit->unit_number : '-',
                    number_format((float) $p->amount, 2),
                    optional($p->due_date)->toDateString(),
                    $p->status,
                    $p->transaction_id ?? '-',
                    $p->receipt_url ?? '-',
                    optional($p->verified_at)->toDateTimeString() ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/{role}/payments/export/pdf
     */
    public function paymentsPdf(Request $request)
    {
        $query = Payment::with('tenant.user:id,full_name', 'unit:id,unit_number,property_id');
        $this->scopePaymentsToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->latest('due_date')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.payments-pdf', [
            'payments'    => $payments,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
        ]);

        return $pdf->download('payments_export_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * GET /api/{role}/maintenance/export/csv
     */
    public function maintenanceCsv(Request $request): StreamedResponse
    {
        $query = MaintenanceRequest::with('tenant.user:id,full_name', 'unit:id,unit_number', 'property:id,name');
        $this->scopeMaintenanceToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->latest()->get();

        $filename = 'maintenance_export_' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($requests) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Tenant', 'Property', 'Unit', 'Category', 'Subject', 'Priority', 'Status', 'Submitted At']);

            foreach ($requests as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->tenant && $r->tenant->user ? $r->tenant->user->full_name : '-',
                    $r->property ? $r->property->name : '-',
                    $r->unit ? $r->unit->unit_number : '-',
                    $r->category,
                    $r->subject ?? '-',
                    $r->priority,
                    $r->status,
                    optional($r->submitted_at)->toDateTimeString() ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/{role}/maintenance/export/pdf
     */
    public function maintenancePdf(Request $request)
    {
        $query = MaintenanceRequest::with('tenant.user:id,full_name', 'unit:id,unit_number', 'property:id,name');
        $this->scopeMaintenanceToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.maintenance-pdf', [
            'requests'    => $requests,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
        ]);

        return $pdf->download('maintenance_export_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Admin sees everything; landlord sees only payments on units within
     * their own properties.
     */
    private function scopePaymentsToRole(Request $request, $query): void
    {
        if ($request->user()->role === 'landlord') {
            $unitIds = Unit::whereHas('property', fn ($q) =>
                    $q->where('landlord_id', $request->user()->id))
                ->pluck('id');

            $query->whereIn('unit_id', $unitIds);
        }
        // admin: no scoping, sees all
    }

    private function scopeMaintenanceToRole(Request $request, $query): void
    {
        if ($request->user()->role === 'landlord') {
            $query->whereHas('property', fn ($q) =>
                $q->where('landlord_id', $request->user()->id));
        }
        // admin: no scoping, sees all
    }
}