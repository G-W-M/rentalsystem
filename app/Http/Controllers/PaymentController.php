<?php

namespace App\Http\Controllers;

use App\Events\PaymentVerified;
use App\Models\Notifications;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * GET /api/tenant/payments
     */
    public function tenantHistory(Request $request): JsonResponse
    {
        $payments = Payment::where('tenant_id', $request->user()->id)
            ->latest('due_date')
            ->paginate(15);

        return response()->json($payments);
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
}