<?php

namespace Database\Seeders;

use App\Models\Landlord;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        DB::table('reports')->updateOrInsert(
            [
                'generated_by' => $admin->id,
                'report_type' => 'financial',
                'title' => 'Landlord Financial Snapshot',
            ],
            [
                'description' => 'Auto-generated financial summary for demo data.',
                'date_range_start' => now()->startOfMonth(),
                'date_range_end' => now()->endOfMonth(),
                'filters' => json_encode(['landlord_id' => $landlord->getKey()]),
                'data' => json_encode(['total_collected' => (float) $totalCollected]),
                'format' => 'pdf',
                'generated_at' => now(),
                'is_scheduled' => false,
            ]
        );
    }
}
