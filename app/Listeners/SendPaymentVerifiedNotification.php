<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendPaymentVerifiedNotification
{
    /**
     * When a payment is verified, notify the tenant's landlord that funds were
     * confirmed. The tenant is already notified inline by PaymentController; this
     * listener adds the landlord-side notification. Auto-discovered by Laravel.
     */
    public function handle(PaymentVerified $event): void
    {
        $payment = $event->payment;

        if ($payment->unit_id === null
            || ! Schema::hasTable('units')
            || ! Schema::hasTable('properties')) {
            return;
        }

        $landlordId = DB::table('units')
            ->join('properties', 'units.property_id', '=', 'properties.id')
            ->where('units.id', $payment->unit_id)
            ->value('properties.landlord_id');

        if ($landlordId === null) {
            return;
        }

        Notifications::create([
            'user_id' => $landlordId,
            'title'   => 'Payment confirmed',
            'message' => 'A tenant payment of ' . number_format((float) $payment->amount, 2)
                . ' was verified.',
            'type'    => 'payment',
        ]);
    }
}