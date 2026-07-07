<?php

namespace Database\Seeders;

use App\Models\Caretaker;
use App\Models\Landlord;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\TenantOccupancy;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rental.com'],
            [
                'username' => 'admin',
                'full_name' => 'Rental Admin',
                'phone' => '0712345678',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ]
        );

        $landlordUser = User::firstOrCreate(
            ['email' => 'landlord@rental.com'],
            [
                'username' => 'demo_landlord',
                'full_name' => 'Demo Landlord',
                'phone' => '0723456789',
                'role' => User::ROLE_LANDLORD,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ]
        );

        $landlord = Landlord::firstOrCreate(
            ['user_id' => $landlordUser->id],
            [
                'company_name' => 'Greenview Properties',
                'kra_pin' => 'A123456789B',
                'physical_address' => 'Kilimani, Nairobi',
                'is_verified' => true,
                'verification_date' => Carbon::now()->subMonth(),
                'registration_date' => Carbon::now()->subMonths(6),
            ]
        );


        $caretakerUser = User::firstOrCreate(
            ['email' => 'caretaker@rental.com'],
            [
                'username' => 'demo_caretaker',
                'full_name' => 'Demo Caretaker',
                'phone' => '0745678901',
                'role' => User::ROLE_CARETAKER,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ]
        );

        $caretaker = Caretaker::firstOrCreate(
            ['user_id' => $caretakerUser->id],
            [
                'landlord_id' => $landlord->getKey(),
                'hire_date' => Carbon::now()->subMonths(3),
                'salary' => 30000,
                'is_active' => true,
            ]
        );

        $property = Property::firstOrCreate(
            [
                'landlord_id' => $landlord->getKey(),
                'name' => 'Greenview Apartments',
            ],
            [
                'address' => 'Kilimani, Nairobi',
                'property_type' => 'apartment',
                'status' => 'active',
            ]
        );

        $unit = Unit::firstOrCreate(
            [
                'property_id' => $property->id,
                'unit_number' => 'A1',
            ],
            [
                'rent_amount' => 25000,
                'status' => Unit::STATUS_AVAILABLE,
            ]
        );

        $tenantUser = User::firstOrCreate(
            ['email' => 'tenant@rental.com'],
            [
                'username' => 'demo_tenant',
                'full_name' => 'Demo Tenant',
                'phone' => '0734567890',
                'role' => User::ROLE_TENANT,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['user_id' => $tenantUser->id],
            [
                'landlord_id' => $landlord->getKey(),
                'is_active' => true,
                'moved_in_date' => Carbon::now()->subDays(30),
            ]
        );

        TenantOccupancy::firstOrCreate(
            [
                'tenant_id' => $tenant->getKey(),
                'unit_id' => $unit->id,
                'is_current' => true,
            ],
            [
                'start_date' => Carbon::now()->subDays(30),
                'rent_amount_at_start' => $unit->rent_amount,
                'created_by' => $admin->id,
            ]
        );

        $unit->update(['status' => Unit::STATUS_OCCUPIED]);

        Payment::firstOrCreate(
            [
                'tenant_id' => $tenant->getKey(),
                'transaction_id' => 'MPESA-001',
            ],
            [
                'unit_id' => $unit->id,
                'amount' => $unit->rent_amount,
                'due_date' => Carbon::now()->startOfMonth()->addDays(4),
                'payment_date' => Carbon::now()->subDays(5),
                'payment_method' => 'mpesa',
                'status' => Payment::STATUS_COMPLETED,
                'verified_by' => $caretaker->getKey(),
                'verified_at' => Carbon::now()->subDays(4),
            ]
        );

        MaintenanceRequest::firstOrCreate(
            [
                'tenant_id' => $tenant->getKey(),
                'unit_id' => $unit->id,
                'description' => 'Leaking kitchen tap',
            ],
            [
                'property_id' => $property->id,
                'category' => MaintenanceRequest::CATEGORY_PLUMBING,
                'subject' => 'Kitchen tap leak',
                'priority' => MaintenanceRequest::PRIORITY_MEDIUM,
                'status' => MaintenanceRequest::STATUS_SUBMITTED,
                'submitted_at' => Carbon::now(),
            ]
        );
    }
}
