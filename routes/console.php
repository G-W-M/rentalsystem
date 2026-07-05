<?php

use App\Models\Payment;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled: flag overdue payments
|--------------------------------------------------------------------------
| The payments.status enum has no dedicated "late" state, so overdue is a
| derived condition (pending + past due_date). This command appends an
| overdue note once per pending, past-due payment for reporting, without
| mutating the enum. Runs daily.
*/
Artisan::command('payments:flag-overdue', function () {
    $count = 0;

    Payment::where('status', 'pending')
        ->whereNotNull('due_date')
        ->whereDate('due_date', '<', now()->toDateString())
        ->whereNull('verified_at')
        ->chunkById(200, function ($payments) use (&$count) {
            foreach ($payments as $payment) {
                if (! str_contains((string) $payment->notes, '[OVERDUE]')) {
                    $payment->update([
                        'notes' => trim(($payment->notes ? $payment->notes . ' ' : '') . '[OVERDUE]'),
                    ]);
                    $count++;
                }
            }
        });

    $this->info("Flagged {$count} overdue payment(s).");
})->purpose('Flag pending payments past their due date');

Schedule::command('payments:flag-overdue')->dailyAt('01:00');