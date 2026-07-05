<?php

namespace Database\Seeders;

use App\Models\Landlord;
use App\Models\Payment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();
        $landlord = Landlord::query()->first();

        if (! $admin || ! $landlord) {
            return;
        }

        $totalCollected = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereHas('unit.property', fn ($q) => $q->where('landlord_id', $landlord->getKey()))
            ->sum('amount');

        Report::query()->updateOrCreate(
            [
                'generated_by' => $admin->id,
                'report_type' => Report::TYPE_FINANCIAL,
                'title' => 'Landlord Financial Snapshot',
            ],
            [
                'description' => 'Auto-generated financial summary for demo data.',
                'date_range_start' => now()->startOfMonth(),
                'date_range_end' => now()->endOfMonth(),
                'filters' => ['landlord_id' => $landlord->getKey()],
                'data' => ['total_collected' => (float) $totalCollected],
                'format' => Report::FORMAT_PDF,
                'generated_at' => now(),
                'is_scheduled' => false,
            ]
        );
    }
}
