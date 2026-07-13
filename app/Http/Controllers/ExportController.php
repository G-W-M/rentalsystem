<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV/PDF export for the whole platform.
 *
 * Payments + Maintenance are visible to BOTH admin (unscoped, all data)
 * and landlord (scoped to their own properties) — scope is applied based
 * on the authenticated user's role.
 *
 * Users, Properties and Units exports are ADMIN-ONLY (system-wide) and
 * are registered only under the admin route group. They give the admin a
 * full picture of everything in the system, matching the landlord's
 * existing download pattern.
 *
 * All exports accept optional filters via query string; omit them to
 * export everything:
 *   ?from=YYYY-MM-DD   &to=YYYY-MM-DD    (date range)
 *   ?status=pending    (payments / maintenance / units)
 *   ?role=tenant       (users)
 *   ?is_active=1|0     (users)
 */
class ExportController extends Controller
{
    // ============================================================
    // PAYMENTS
    // ============================================================

    /**
     * GET /api/{role}/payments/export/csv
     */
    public function paymentsCsv(Request $request): StreamedResponse
    {
        $payments = $this->paymentsQuery($request)->get();

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
        $payments = $this->paymentsQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.payments-pdf', [
            'payments'    => $payments,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
            'filters'     => $this->activeFilters($request),
        ]);

        return $pdf->download('payments_export_' . now()->format('Ymd_His') . '.pdf');
    }

    private function paymentsQuery(Request $request)
    {
        $query = Payment::with('tenant.user:id,full_name', 'unit:id,unit_number,property_id');
        $this->scopePaymentsToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $this->applyDateRange($request, $query, 'due_date');

        return $query->latest('due_date');
    }

    // ============================================================
    // MAINTENANCE
    // ============================================================

    /**
     * GET /api/{role}/maintenance/export/csv
     */
    public function maintenanceCsv(Request $request): StreamedResponse
    {
        $requests = $this->maintenanceQuery($request)->get();

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
        $requests = $this->maintenanceQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.maintenance-pdf', [
            'requests'    => $requests,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
            'filters'     => $this->activeFilters($request),
        ]);

        return $pdf->download('maintenance_export_' . now()->format('Ymd_His') . '.pdf');
    }

    private function maintenanceQuery(Request $request)
    {
        $query = MaintenanceRequest::with('tenant.user:id,full_name', 'unit:id,unit_number', 'property:id,name');
        $this->scopeMaintenanceToRole($request, $query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $this->applyDateRange($request, $query, 'submitted_at');

        return $query->latest();
    }

    // ============================================================
    // USERS — ADMIN ONLY (system-wide)
    // ============================================================

    /**
     * GET /api/admin/users/export/csv
     * All users across every role. Filter with ?role= and ?is_active=
     */
    public function usersCsv(Request $request): StreamedResponse
    {
        $users = $this->usersQuery($request)->get();

        $filename = 'users_export_' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Full Name', 'Username', 'Email', 'Phone', 'Role', 'Status', 'Last Login', 'Registered At']);

            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->full_name,
                    $u->username ?? '-',
                    $u->email,
                    $u->phone ?? '-',
                    $u->role,
                    $u->is_active ? 'Active' : 'Inactive',
                    optional($u->last_login)->toDateTimeString() ?? 'Never',
                    optional($u->created_at)->toDateTimeString() ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/admin/users/export/pdf
     */
    public function usersPdf(Request $request)
    {
        $users = $this->usersQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.users-pdf', [
            'users'       => $users,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
            'filters'     => $this->activeFilters($request),
        ]);

        return $pdf->download('users_export_' . now()->format('Ymd_His') . '.pdf');
    }

    private function usersQuery(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $this->applyDateRange($request, $query, 'created_at');

        return $query->orderBy('role')->orderBy('full_name');
    }

    // ============================================================
    // PROPERTIES — ADMIN ONLY (system-wide)
    // ============================================================

    /**
     * GET /api/admin/properties/export/csv
     * Every property in the system, with its landlord and unit counts.
     */
    public function propertiesCsv(Request $request): StreamedResponse
    {
        $properties = $this->propertiesQuery($request)->get();

        $filename = 'properties_export_' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($properties) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Property Name', 'Landlord', 'Address', 'Type', 'Total Units', 'Occupied', 'Available', 'Caretaker']);

            foreach ($properties as $p) {
                $units     = $p->units ?? collect();
                $occupied  = $units->where('status', 'occupied')->count();
                $available = $units->where('status', 'available')->count();

                fputcsv($handle, [
                    $p->id,
                    $p->name,
                    $p->landlord && $p->landlord->user ? $p->landlord->user->full_name : '-',
                    $p->address ?? '-',
                    $p->type ?? '-',
                    $units->count(),
                    $occupied,
                    $available,
                    $p->caretaker && $p->caretaker->user ? $p->caretaker->user->full_name : 'Unassigned',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/admin/properties/export/pdf
     */
    public function propertiesPdf(Request $request)
    {
        $properties = $this->propertiesQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.properties-pdf', [
            'properties'  => $properties,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
            'filters'     => $this->activeFilters($request),
        ]);

        return $pdf->download('properties_export_' . now()->format('Ymd_His') . '.pdf');
    }

    private function propertiesQuery(Request $request)
    {
        $query = Property::with([
            'landlord.user:id,full_name',
            'caretaker.user:id,full_name',
            'units:id,property_id,status',
        ]);

        if ($request->filled('landlord_id')) {
            $query->where('landlord_id', $request->input('landlord_id'));
        }

        $this->applyDateRange($request, $query, 'created_at');

        return $query->orderBy('name');
    }

    // ============================================================
    // UNITS — ADMIN ONLY (system-wide)
    // ============================================================

    /**
     * GET /api/admin/units/export/csv
     * Every unit in the system. Filter with ?status=available|occupied|maintenance
     */
    public function unitsCsv(Request $request): StreamedResponse
    {
        $units = $this->unitsQuery($request)->get();

        $filename = 'units_export_' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($units) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Unit Number', 'Property', 'Landlord', 'Rent Amount', 'Status', 'Current Tenant']);

            foreach ($units as $u) {
                $occupancy = $u->activeOccupancy ?? null;
                $tenantName = $occupancy && $occupancy->tenant && $occupancy->tenant->user
                    ? $occupancy->tenant->user->full_name
                    : '-';

                fputcsv($handle, [
                    $u->id,
                    $u->unit_number,
                    $u->property ? $u->property->name : '-',
                    $u->property && $u->property->landlord && $u->property->landlord->user
                        ? $u->property->landlord->user->full_name
                        : '-',
                    number_format((float) $u->rent_amount, 2),
                    $u->status,
                    $tenantName,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/admin/units/export/pdf
     */
    public function unitsPdf(Request $request)
    {
        $units = $this->unitsQuery($request)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.units-pdf', [
            'units'       => $units,
            'generatedAt' => now(),
            'generatedBy' => $request->user()->full_name,
            'filters'     => $this->activeFilters($request),
        ]);

        return $pdf->download('units_export_' . now()->format('Ymd_His') . '.pdf');
    }

    private function unitsQuery(Request $request)
    {
        $query = Unit::with([
            'property:id,name,landlord_id',
            'property.landlord.user:id,full_name',
            'activeOccupancy.tenant.user:id,full_name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        return $query->orderBy('property_id')->orderBy('unit_number');
    }

    // ============================================================
    // SHARED HELPERS
    // ============================================================

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

    /**
     * Optional ?from= / ?to= date filtering. Both are inclusive. Applied
     * against whichever column makes sense for that export (due_date for
     * payments, submitted_at for maintenance, created_at for users etc).
     */
    private function applyDateRange(Request $request, $query, string $column): void
    {
        if ($request->filled('from')) {
            $query->whereDate($column, '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate($column, '<=', $request->input('to'));
        }
    }

    /**
     * Human-readable summary of which filters were applied, printed at the
     * top of every PDF so the report is self-describing (important when a
     * filtered export gets shared or filed away).
     */
    private function activeFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('from')) {
            $filters['From'] = $request->input('from');
        }
        if ($request->filled('to')) {
            $filters['To'] = $request->input('to');
        }
        if ($request->filled('status')) {
            $filters['Status'] = ucfirst($request->input('status'));
        }
        if ($request->filled('role')) {
            $filters['Role'] = ucfirst($request->input('role'));
        }
        if ($request->filled('is_active')) {
            $filters['Account Status'] = $request->input('is_active') ? 'Active' : 'Inactive';
        }

        return $filters;
    }
}
