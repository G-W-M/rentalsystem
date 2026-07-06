<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\TenantOccupancy;
use Illuminate\Console\Command;

/**
 * Generates the next month's rent invoice for every tenant with a current
 * occupancy, once their most recent payment is settled (completed) and no
 * future-dated pending payment already exists. Mirrors the FR's implied
 * monthly billing cycle (payment history is described by "Billing Month").
 */
class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'payments:generate-monthly';
    protected $description = 'Generate the next pending rent invoice for tenants whose latest payment is settled';

    public function handle(): int
    {
        $occupancies = TenantOccupancy::where('is_current', true)->with('unit')->get();
        $created = 0;

        foreach ($occupancies as $occupancy) {
            $latest = Payment::where('tenant_id', $occupancy->tenant_id)
                ->where('unit_id', $occupancy->unit_id)
                ->latest('due_date')
                ->first();

            $hasPendingFuture = Payment::where('tenant_id', $occupancy->tenant_id)
                ->where('unit_id', $occupancy->unit_id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingFuture) {
                continue;
            }

            if ($latest && $latest->status !== 'completed') {
                continue;
            }

            $nextDueDate = $latest
                ? \Carbon\Carbon::parse($latest->due_date)->addMonthNoOverflow()
                : now()->addMonthNoOverflow();

            Payment::create([
                'tenant_id' => $occupancy->tenant_id,
                'unit_id'   => $occupancy->unit_id,
                'amount'    => $occupancy->unit->rent_amount,
                'due_date'  => $nextDueDate,
                'status'    => 'pending',
                'notes'     => 'Auto-generated monthly rent invoice.',
            ]);

            $created++;
        }

        $this->info("Generated {$created} new monthly invoice(s).");
        return self::SUCCESS;
    }
}
