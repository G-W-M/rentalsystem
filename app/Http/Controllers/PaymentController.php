<?php

namespace App\Http\Controllers;

use App\Events\PaymentVerified;
use App\Models\Notifications;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantOccupancy;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * GET /api/tenant/payments
     */
    public function tenantHistory(Request $request): JsonResponse
    {
        $payments = Payment::where('tenant_id', $request->user()->id)
            ->latest('due_date')
            ->with('unit.property')
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * GET /api/tenant/units
     * Get units assigned to the authenticated tenant
     */
    public function tenantUnits(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get the tenant record
        $tenant = Tenant::where('user_id', $user->id)->first();
        
        if (!$tenant) {
            return response()->json(['message' => 'Tenant profile not found.'], 404);
        }
        
        // Get units assigned to this tenant
        $units = Unit::where('tenant_id', $tenant->id)
            ->with('property')
            ->get();
        
        return response()->json($units);
    }

    /**
     * POST /api/tenant/payments/{payment}/transaction-code
     */
    public function submitTransactionCode(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->tenant_id === $request->user()->id, 403, 'Not your payment.');

        $request->validate([
            'transaction_id' => ['required', 'string', 'max:100', 'unique:payments,transaction_id'],
            'payment_method' => ['required', 'string', 'max:50'],
        ]);

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'This payment is already completed.'], 422);
        }

        $payment->update([
            'transaction_id' => $request->transaction_id,
            'payment_method' => $request->payment_method,
            'payment_date'   => now()->toDateString(),
            'status'         => 'pending',
        ]);

        return response()->json([
            'message' => 'Transaction code submitted. Awaiting verification.',
            'payment' => $payment,
        ]);
    }

    /**
     * POST /api/tenant/payments
     * Tenant adds a new payment with transaction details
     */
    public function addPayment(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get the tenant record
        $tenant = Tenant::where('user_id', $user->id)->first();
        
        if (!$tenant) {
            return response()->json(['message' => 'Tenant profile not found.'], 404);
        }

        $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'transaction_id' => ['required', 'string', 'max:100', 'unique:payments,transaction_id'],
            'payment_method' => ['required', 'string', 'max:50'],
        ]);

        // Verify tenant owns this unit
        $unit = Unit::find($request->unit_id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        // Check if tenant is assigned to this unit
        if ($unit->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'You are not assigned to this unit.'], 403);
        }

        // Check if payment already exists for this month
        $existingPayment = Payment::where('unit_id', $request->unit_id)
            ->where('tenant_id', $tenant->id)
            ->whereMonth('due_date', date('m', strtotime($request->due_date)))
            ->whereYear('due_date', date('Y', strtotime($request->due_date)))
            ->first();

        if ($existingPayment) {
            return response()->json([
                'message' => 'You already have a payment for this month.',
                'payment' => $existingPayment
            ], 422);
        }

        $payment = Payment::create([
            'unit_id' => $request->unit_id,
            'tenant_id' => $tenant->id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'transaction_id' => $request->transaction_id,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
        ]);

        // Notify tenant
        Notifications::create([
            'user_id' => $user->id,
            'title' => 'Payment submitted',
            'message' => 'Your payment of ' . number_format($request->amount, 2) . 
                        ' has been submitted for verification.',
            'type' => 'payment',
        ]);

        return response()->json([
            'message' => 'Payment submitted successfully. Awaiting verification.',
            'payment' => $payment
        ], 201);
    }

    /**
     * POST /api/caretaker/payments/{payment}/verify
     *
     * HARD RULES:
     *   1. The amount can NEVER be supplied by the caretaker — it is read
     *      from the stored record only.
     *   2. The caretaker must independently enter the transaction code they
     *      were given (e.g. by the tenant, verbally or via receipt) and it
     *      must EXACTLY MATCH the code the tenant already submitted via
     *      submitTransactionCode(). A mismatch hard-blocks verification —
     *      this is a second, independent confirmation of the same
     *      transaction, not a formality.
     */
    public function verify(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'transaction_code' => ['required', 'string', 'max:100'],
        ]);

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Already verified.'], 422);
        }

        if ($payment->transaction_id === null) {
            return response()->json(['message' => 'No transaction code to verify yet.'], 422);
        }

        if (! hash_equals((string) $payment->transaction_id, (string) $request->input('transaction_code'))) {
            return response()->json([
                'message' => 'The code you entered does not match the tenant\'s submitted transaction code. Verification blocked.',
            ], 422);
        }

        $payment = DB::transaction(function () use ($payment, $request) {
            $receiptNo = 'RCP-' . now()->format('Ym') . '-'
                . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);

            $payment->update([
                'status'      => 'completed',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'receipt_url' => $receiptNo,
            ]);

            Notifications::create([
                'user_id' => $payment->tenant_id,
                'title'   => 'Payment verified',
                'message' => 'Your payment was verified. Receipt ' . $receiptNo . '.',
                'type'    => 'payment',
            ]);

            return $payment;
        });

        event(new PaymentVerified($payment));

        return response()->json(['message' => 'Payment verified.', 'payment' => $payment]);
    }

    /**
     * GET /api/landlord/payments
     */
    public function landlordIndex(Request $request): JsonResponse
    {
        $unitIds = Unit::whereHas('property', fn ($q) =>
                $q->where('landlord_id', $request->user()->id))
            ->pluck('id');

        $query = Payment::whereIn('unit_id', $unitIds)
            ->with('tenant.user:id,full_name', 'unit:id,unit_number,property_id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->latest('due_date')->paginate(20));
    }

    /**
     * GET /api/admin/payments
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Payment::with('tenant.user:id,full_name', 'unit:id,unit_number,property_id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->latest('due_date')->paginate(20));
    }

    /**
     * GET /api/caretaker/payments/verified
     */
    public function caretakerVerifiedIndex(Request $request): JsonResponse
    {
        $payments = Payment::where('verified_by', $request->user()->id)
            ->where('status', 'completed')
            ->with('tenant.user:id,full_name', 'unit:id,unit_number')
            ->latest('verified_at')
            ->paginate(20);

        return response()->json($payments);
    }

    // ============================================================
    // NEW METHODS ADDED BELOW
    // ============================================================

    /**
     * GET /tenant/pay-rent
     * Called by the "Pay Rent" button.
     * Returns the payment the tenant should pay — current month if unpaid,
     * or generates next month's row if current month is already completed.
     */
    public function payRent(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get active occupancy → unit (matches your tenants.user_id = users.id pattern)
        $occupancy = TenantOccupancy::where('tenant_id', $userId)
            ->where('is_current', true)
            ->with('unit')
            ->first();

        if (! $occupancy || ! $occupancy->unit) {
            return response()->json([
                'message' => 'You are not currently assigned to a unit. Please contact your landlord.',
            ], 422);
        }

        $unit = $occupancy->unit;
        $now  = Carbon::now();

        // Find this month's payment row.
        $current = Payment::where('tenant_id', $userId)
            ->where('unit_id', $unit->id)
            ->whereYear('due_date', $now->year)
            ->whereMonth('due_date', $now->month)
            ->first();

        // Case 1: row exists and is not yet completed → pay it.
        if ($current && $current->status !== 'completed') {
            return response()->json([
                'advance' => false,
                'payment' => $this->paymentPayload($current, $unit),
            ]);
        }

        // Case 2: no row yet this month → generate it now.
        if (! $current) {
            $current = $this->generatePaymentRow($userId, $unit, $now);
            return response()->json([
                'advance' => false,
                'payment' => $this->paymentPayload($current, $unit),
            ]);
        }

        // Case 3: current month completed → offer next month in advance.
        $lastDue         = Payment::where('tenant_id', $userId)->where('unit_id', $unit->id)->max('due_date');
        $nextMonthAnchor = Carbon::parse($lastDue)->addMonthNoOverflow();

        // Guard: don't go past lease end.
        if ($occupancy->end_date && $nextMonthAnchor->greaterThan(Carbon::parse($occupancy->end_date))) {
            return response()->json([
                'message' => 'Your rent is fully paid up to the end of your current lease.',
            ], 422);
        }

        // Reuse existing next-month row if it already exists.
        $next = Payment::where('tenant_id', $userId)
            ->where('unit_id', $unit->id)
            ->whereYear('due_date', $nextMonthAnchor->year)
            ->whereMonth('due_date', $nextMonthAnchor->month)
            ->first() ?? $this->generatePaymentRow($userId, $unit, $nextMonthAnchor);

        return response()->json([
            'advance' => true,
            'message' => "This month's rent is already paid. Would you like to pay next month in advance?",
            'payment' => $this->paymentPayload($next, $unit),
        ]);
    }

    /**
     * POST /api/tenant/payments/{payment}/submit
     * Tenant submits a transaction reference (Pay Rent flow + per-row Submit Code flow).
     */
    public function submitPayment(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->tenant_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorised.'], 403);
        }

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'This payment is already completed.'], 422);
        }

        $data = $request->validate([
            'transaction_id' => ['required', 'string', 'max:100', 'unique:payments,transaction_id'],
            'payment_method' => ['required', 'in:mpesa,bank,cash,cheque'],
        ]);

        $isAdvance = Carbon::parse($payment->due_date)->isFuture();

        $payment->update([
            'transaction_id' => $data['transaction_id'],
            'payment_method' => $data['payment_method'],
            'payment_date'   => now()->toDateString(),
            'status'         => 'pending',
            'notes'          => ($isAdvance ? 'Advance payment. ' : '')
                              . 'Submitted by tenant, awaiting verification.',
        ]);

        // Notify landlord (properties link to landlord, not directly to caretaker).
        $landlordId = optional(optional($payment->unit)->property)->landlord_id;
        if ($landlordId) {
            Notifications::create([
                'user_id' => $landlordId,
                'title'   => 'Payment Awaiting Verification',
                'message' => 'A tenant submitted a rent payment reference for verification.',
                'type'    => 'payment',
                'link'    => '/landlord/payments',
            ]);
        }

        return response()->json([
            'ok'      => true,
            'advance' => $isAdvance,
            'message' => $isAdvance
                ? 'Advance payment submitted. It will reflect once verified.'
                : 'Payment submitted. It will reflect once the caretaker verifies it.',
        ], 201);
    }

    /**
     * GET /tenant/payments/{payment}/receipt
     * Streams a PDF receipt for a completed payment.
     */
    public function downloadReceipt(Request $request, Payment $payment)
    {
        if ($payment->tenant_id !== $request->user()->id) {
            abort(403, 'Not authorised.');
        }

        if ($payment->status !== 'completed') {
            abort(422, 'Receipt is only available for completed payments.');
        }

        $payment->load('unit.property', 'tenant.user');

        $pdf = Pdf::loadView('tenant.receipt', [
            'payment'  => $payment,
            'unit'     => $payment->unit,
            'property' => $payment->unit->property,
        ])->setPaper('a4', 'portrait');

        $filename = 'receipt-' . ($payment->receipt_url ?? $payment->id) . '.pdf';

        return $pdf->download($filename);
    }

    /* ── private helpers ─────────────────────────────────────── */

    private function generatePaymentRow(int $userId, Unit $unit, Carbon $anchor): Payment
    {
        return Payment::create([
            'tenant_id'      => $userId,
            'unit_id'        => $unit->id,
            'amount'         => $unit->rent_amount,
            'payment_date'   => null,
            'due_date'       => $anchor->copy()->startOfMonth()->addDays(4), // due on the 5th
            'payment_method' => null,
            'transaction_id' => null,
            'status'         => 'pending',
            'receipt_url'    => null,
            'verified_by'    => null,
            'verified_at'    => null,
            'notes'          => null,
        ]);
    }

    private function paymentPayload(Payment $payment, Unit $unit): array
    {
        return [
            'id'       => $payment->id,
            'amount'   => $payment->amount,
            'due_date' => Carbon::parse($payment->due_date)->toDateString(),
            'unit'     => $unit->unit_number,
            'status'   => $payment->status,
        ];
    }
}