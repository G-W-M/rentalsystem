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
        $caretaker = \App\Models\Caretaker::where('user_id', $request->user()->id)->first();

        if (! $caretaker || ! $caretaker->property_id) {
            return response()->json(['message' => 'No property assigned to this caretaker.'], 403);
        }

        $unitIds = Unit::where('property_id', $caretaker->property_id)->pluck('id');

        $payments = Payment::whereIn('unit_id', $unitIds)
            ->where('verified_by', $request->user()->id)
            ->where('status', 'completed')
            ->with([
                'tenant.user:id,full_name',
                'unit:id,unit_number',
            ])
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
    public function pendingForCaretaker(Request $request): JsonResponse
    {
        $caretaker = \App\Models\Caretaker::where('user_id', $request->user()->id)->first();

        if (! $caretaker || ! $caretaker->property_id) {
            return response()->json(['message' => 'No property assigned to this caretaker.'], 403);
        }

        // Get all unit IDs for the caretaker's property.
        $unitIds = Unit::where('property_id', $caretaker->property_id)->pluck('id');

        $payments = Payment::whereIn('unit_id', $unitIds)
            ->where('status', 'pending')
            ->whereNotNull('transaction_id')   // tenant has submitted a code — awaiting verification
            ->with([
                'tenant.user:id,full_name,email,phone',
                'unit:id,unit_number,property_id',
            ])
            ->latest('payment_date')
            ->get();

        return response()->json($payments);
    }
     public function payRent(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $occupancy = \App\Models\TenantOccupancy::where('tenant_id', $userId)
            ->where('is_current', true)
            ->first();

        if (! $occupancy) {
            return response()->json(['message' => 'No active unit found.'], 422);
        }

        $unit       = Unit::find($occupancy->unit_id);
        $rentAmount = $occupancy->rent_amount_at_start ?? $unit->rent_amount;
        $now        = Carbon::now();

        // Lease runs up to end_date, max 24 months ahead
        $leaseEnd = $occupancy->end_date
            ? Carbon::parse($occupancy->end_date)->startOfMonth()
            : $now->copy()->addMonths(24)->startOfMonth();

        // Build array of all months from current month to lease end
        $allMonths = [];
        $start = $now->copy()->startOfMonth();
        $temp  = $start->copy();
        $count = 0;

        while ($temp->lessThanOrEqualTo($leaseEnd) && $count < 24) {
            $allMonths[] = $temp->copy();
            $temp->addMonthNoOverflow();
            $count++;
        }

        // Get all payment rows for this tenant+unit in one query (no N+1)
        $existingPayments = Payment::where('tenant_id', $userId)
            ->where('unit_id', $occupancy->unit_id)
            ->get()
            ->keyBy(fn($p) => Carbon::parse($p->due_date)->format('Y-m'));

        // Build payable months list — skip months that are already completed
        $payableMonths = [];

        foreach ($allMonths as $month) {
            $key      = $month->format('Y-m');
            $existing = $existingPayments->get($key);

            // Skip if already fully paid
            if ($existing && $existing->status === 'completed') {
                continue;
            }

            $due = $month->copy()->addDays(4); // due on day 5

            $payableMonths[] = [
                'payment_id'     => $existing?->id,
                'month_key'      => $key,
                'label'          => $month->format('F Y'),
                'amount'         => number_format((float)$rentAmount, 2, '.', ''),
                'due_date'       => $due->toDateString(),
                'status'         => $existing?->status ?? 'pending',
                'transaction_id' => $existing?->transaction_id,
            ];
        }

        if (empty($payableMonths)) {
            return response()->json([
                'message' => 'Your rent is fully paid up to the end of your lease.',
            ], 422);
        }

        return response()->json([
            'unit'           => $unit->unit_number,
            'payable_months' => $payableMonths,
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
    }  public function initAndSubmit(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'due_date'       => ['required', 'date'],
            'transaction_id' => ['required', 'string', 'max:100', 'unique:payments,transaction_id'],
            'payment_method' => ['required', 'in:mpesa,bank,cash,cheque'],
        ]);

        $occupancy = \App\Models\TenantOccupancy::where('tenant_id', $userId)
            ->where('is_current', true)
            ->with('unit')
            ->first();

        if (! $occupancy || ! $occupancy->unit) {
            return response()->json(['message' => 'No active unit found.'], 422);
        }

        $unit       = $occupancy->unit;
        $dueCarbon  = Carbon::parse($data['due_date']);
        $rentAmount = $occupancy->rent_amount_at_start ?? $unit->rent_amount;

        // Hard rule: no duplicate completed payment for this month
        $existing = Payment::where('tenant_id', $userId)
            ->where('unit_id', $unit->id)
            ->whereYear('due_date', $dueCarbon->year)
            ->whereMonth('due_date', $dueCarbon->month)
            ->where('status', 'completed')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Rent for ' . $dueCarbon->format('F Y') . ' is already paid.',
            ], 422);
        }

        $isAdvance = $dueCarbon->isFuture();

        $payment = Payment::firstOrCreate(
            [
                'tenant_id' => $userId,
                'unit_id'   => $unit->id,
                'due_date'  => $data['due_date'],
            ],
            [
                'amount'         => $rentAmount,
                'status'         => 'pending',
                'payment_date'   => null,
                'payment_method' => null,
                'transaction_id' => null,
                'receipt_url'    => null,
                'verified_by'    => null,
                'verified_at'    => null,
                'notes'          => null,
            ]
        );

        $payment->update([
            'transaction_id' => $data['transaction_id'],
            'payment_method' => $data['payment_method'],
            'payment_date'   => now()->toDateString(),
            'status'         => 'pending',
            'notes'          => ($isAdvance ? 'Advance payment. ' : '')
                              . 'Submitted by tenant, awaiting verification.',
        ]);

        // Notify landlord
        $landlordId = optional($unit->property)->landlord_id;
        if ($landlordId) {
            Notifications::create([
                'user_id' => $landlordId,
                'title'   => 'Payment Awaiting Verification',
                'message' => 'A tenant submitted a ' . ($isAdvance ? 'advance ' : '')
                           . 'rent payment reference for ' . $dueCarbon->format('F Y') . '.',
                'type'    => 'payment',
                'link'    => '/landlord/payments',
            ]);
        }

        return response()->json([
            'ok'      => true,
            'advance' => $isAdvance,
            'message' => ($isAdvance ? 'Advance payment' : 'Payment') . ' submitted. It will reflect once verified.',
        ], 201);
    }

    // ── helper: due date = day 5 of the month ──
    private function dueDate(Carbon $anchor): Carbon
    {
        return $anchor->copy()->startOfMonth()->addDays(4);
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

        // Load all relationships needed by the template.
        // tenant()->user is the actual person; unit->property gives address.
        $payment->load([
            'unit.property',
            'tenant.user',
        ]);

        // receipt_url on older/seeded records may be a file path like
        // "/receipts/Pk4R8wXl.pdf" — extract just the reference portion
        // so it's usable as a display label and safe as a filename.
        $receiptRef = $payment->receipt_url
            ? preg_replace('/[\/\\\]/', '-', basename($payment->receipt_url, '.pdf'))
            : ('PMT-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT));

        $pdf = Pdf::loadView('exports.receipt', [
            'payment'    => $payment,
            'unit'       => $payment->unit,
            'property'   => $payment->unit?->property,
            'receiptRef' => $receiptRef,   // clean reference for display
        ])->setPaper('a4', 'portrait');

        $filename = 'receipt-' . $receiptRef . '.pdf';

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
