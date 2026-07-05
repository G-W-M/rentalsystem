<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FunSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('audit_trails')->truncate();
        DB::table('notifications')->truncate();
        DB::table('reports')->truncate();
        DB::table('settings')->truncate();
        DB::table('daily_activity_logs')->truncate();
        DB::table('sessions')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('tasks')->truncate();
        DB::table('maintenance_requests')->truncate();
        DB::table('payments')->truncate();
        DB::table('tenant_occupancies')->truncate();
        DB::table('tenants')->truncate();
        DB::table('units')->truncate();
        DB::table('properties')->truncate();
        DB::table('caretakers')->truncate();
        DB::table('admins')->truncate();
        DB::table('landlords')->truncate();
        DB::table('personal_access_tokens')->truncate();
        DB::table('password_reset_tokens')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $adminUser = DB::table('users')->insertGetId([
            'username' => 'rental_admin',
            'email' => 'admin@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0712345678',
            'full_name' => 'Rental Admin',
            'role' => 'admin',
            'is_active' => true,
            'last_login' => null,
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $landlordUser = DB::table('users')->insertGetId([
            'username' => 'demo_landlord',
            'email' => 'landlord@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0723456789',
            'full_name' => 'Demo Landlord',
            'role' => 'landlord',
            'is_active' => true,
            'last_login' => null,
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $caretakerUser = DB::table('users')->insertGetId([
            'username' => 'demo_caretaker',
            'email' => 'caretaker@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0745678901',
            'full_name' => 'Demo Caretaker',
            'role' => 'caretaker',
            'is_active' => true,
            'last_login' => null,
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $tenantUser1 = DB::table('users')->insertGetId([
            'username' => 'demo_tenant1',
            'email' => 'tenant1@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0734567890',
            'full_name' => 'Demo Tenant One',
            'role' => 'tenant',
            'is_active' => true,
            'last_login' => null,
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $tenantUser2 = DB::table('users')->insertGetId([
            'username' => 'demo_tenant2',
            'email' => 'tenant2@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0756789012',
            'full_name' => 'Demo Tenant Two',
            'role' => 'tenant',
            'is_active' => true,
            'last_login' => null,
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('admins')->insert([
            'user_id' => $adminUser,
            'admin_level' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('landlords')->insert([
            'user_id' => $landlordUser,
            'company_name' => 'Greenview Properties Ltd',
            'kra_pin' => 'KRA-123456789',
            'physical_address' => 'Nairobi, Kenya',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('caretakers')->insert([
            'user_id' => $caretakerUser,
            'landlord_id' => $landlordUser,
            'hire_date' => Carbon::now()->subMonths(6),
            'salary' => 15000.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $propertyId1 = DB::table('properties')->insertGetId([
            'landlord_id' => $landlordUser,
            'name' => 'Greenview Apartments',
            'address' => 'Kilimani, Nairobi, Kenya',
            'property_type' => 'apartment',
            'status' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $propertyId2 = DB::table('properties')->insertGetId([
            'landlord_id' => $landlordUser,
            'name' => 'Sunset Villas',
            'address' => 'Westlands, Nairobi, Kenya',
            'property_type' => 'apartment',
            'status' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $unit1Id = DB::table('units')->insertGetId([
            'property_id' => $propertyId1,
            'unit_number' => 'A1',
            'rent_amount' => 25000.00,
            'status' => 'occupied',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $unit2Id = DB::table('units')->insertGetId([
            'property_id' => $propertyId1,
            'unit_number' => 'A2',
            'rent_amount' => 30000.00,
            'status' => 'available',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $unit3Id = DB::table('units')->insertGetId([
            'property_id' => $propertyId1,
            'unit_number' => 'B1',
            'rent_amount' => 35000.00,
            'status' => 'maintenance',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $unit4Id = DB::table('units')->insertGetId([
            'property_id' => $propertyId2,
            'unit_number' => 'C1',
            'rent_amount' => 45000.00,
            'status' => 'available',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $unit5Id = DB::table('units')->insertGetId([
            'property_id' => $propertyId2,
            'unit_number' => 'C2',
            'rent_amount' => 50000.00,
            'status' => 'occupied',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $tenant1LeaseStart = Carbon::now()->subMonths(6);
        $tenant1LeaseEnd = Carbon::now()->addMonths(6);
        $tenant2LeaseStart = Carbon::now()->subMonths(3);
        $tenant2LeaseEnd = Carbon::now()->addMonths(9);

        DB::table('tenants')->insert([
            'user_id' => $tenantUser1,
            'landlord_id' => $landlordUser,
            'is_active' => true,
            'moved_in_date' => $tenant1LeaseStart,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tenants')->insert([
            'user_id' => $tenantUser2,
            'landlord_id' => $landlordUser,
            'is_active' => true,
            'moved_in_date' => $tenant2LeaseStart,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tenant_occupancies')->insert([
            'tenant_id' => $tenantUser1,
            'unit_id' => $unit1Id,
            'start_date' => $tenant1LeaseStart,
            'end_date' => $tenant1LeaseEnd,
            'is_current' => true,
            'rent_amount_at_start' => 25000.00,
            'created_by' => $adminUser,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tenant_occupancies')->insert([
            'tenant_id' => $tenantUser2,
            'unit_id' => $unit5Id,
            'start_date' => $tenant2LeaseStart,
            'end_date' => $tenant2LeaseEnd,
            'is_current' => true,
            'rent_amount_at_start' => 50000.00,
            'created_by' => $adminUser,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $paymentMonths = [1, 2, 3, 4, 5];

        foreach ($paymentMonths as $month) {
            $paymentDate = Carbon::now()->subMonths(6 - $month);
            $dueDate = Carbon::now()->subMonths(6 - $month)->addDays(5);
            $status = $month === 4 ? 'pending' : 'completed';

            DB::table('payments')->insert([
                'tenant_id' => $tenantUser1,
                'unit_id' => $unit1Id,
                'amount' => 25000.00,
                'payment_date' => $status === 'completed' ? $paymentDate : null,
                'due_date' => $dueDate,
                'payment_method' => $status === 'completed' ? 'mpesa' : null,
                'transaction_id' => $status === 'completed' ? 'MPESA'.rand(100000, 999999) : null,
                'status' => $status,
                'receipt_url' => $status === 'completed' ? '/receipts/'.rand(1000, 9999).'.pdf' : null,
                'verified_by' => $status === 'completed' ? $caretakerUser : null,
                'verified_at' => $status === 'completed' ? Carbon::now() : null,
                'notes' => $status === 'completed' ? 'Rent payment for month '.$month : null,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
        }

        DB::table('payments')->insert([
            'tenant_id' => $tenantUser2,
            'unit_id' => $unit5Id,
            'amount' => 50000.00,
            'payment_date' => Carbon::now()->subMonths(2),
            'due_date' => Carbon::now()->subMonths(2)->addDays(5),
            'payment_method' => 'bank',
            'transaction_id' => 'BANK'.rand(100000, 999999),
            'status' => 'completed',
            'receipt_url' => '/receipts/'.rand(1000, 9999).'.pdf',
            'verified_by' => $caretakerUser,
            'verified_at' => Carbon::now()->subMonths(2),
            'notes' => 'Rent payment',
            'created_at' => Carbon::now()->subMonths(2),
            'updated_at' => Carbon::now()->subMonths(2),
        ]);

        $mr1Id = DB::table('maintenance_requests')->insertGetId([
            'tenant_id' => $tenantUser1,
            'unit_id' => $unit1Id,
            'property_id' => $propertyId1,
            'category' => 'plumbing',
            'subject' => 'Bathroom ceiling leak',
            'description' => 'Water leakage in the bathroom ceiling. It has been dripping for 3 days.',
            'priority' => 'high',
            'status' => 'assigned',
            'is_major' => true,
            'assigned_to' => $caretakerUser,
            'submitted_at' => Carbon::now()->subDays(5),
            'assigned_at' => Carbon::now()->subDays(4),
            'approved_by_landlord' => true,
            'approved_at' => Carbon::now()->subDays(3),
            'resolution_notes' => null,
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        $mr2Id = DB::table('maintenance_requests')->insertGetId([
            'tenant_id' => $tenantUser2,
            'unit_id' => $unit5Id,
            'property_id' => $propertyId2,
            'category' => 'plumbing',
            'subject' => 'Leaking faucet',
            'description' => 'The kitchen faucet is leaking and needs to be replaced.',
            'priority' => 'medium',
            'status' => 'in_progress',
            'is_major' => false,
            'assigned_to' => $caretakerUser,
            'submitted_at' => Carbon::now()->subDays(2),
            'assigned_at' => Carbon::now()->subDays(1),
            'approved_by_landlord' => false,
            'resolution_notes' => null,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        $mr3Id = DB::table('maintenance_requests')->insertGetId([
            'tenant_id' => $tenantUser1,
            'unit_id' => $unit1Id,
            'property_id' => $propertyId1,
            'category' => 'electrical',
            'subject' => 'Circuit tripping',
            'description' => 'Electrical wiring needs to be updated. The circuit keeps tripping.',
            'priority' => 'emergency',
            'status' => 'submitted',
            'is_major' => true,
            'submitted_at' => Carbon::now()->subDays(1),
            'approved_by_landlord' => false,
            'resolution_notes' => null,
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        DB::table('tasks')->insert([
            'maintenance_request_id' => $mr1Id,
            'assigned_to' => $caretakerUser,
            'task_description' => 'Fix water leakage in bathroom ceiling',
            'priority' => 'high',
            'status' => 'in_progress',
            'started_at' => Carbon::now()->subDays(2),
            'completed_at' => null,
            'completion_notes' => null,
            'is_completed_by_caretaker' => false,
            'created_at' => Carbon::now()->subDays(4),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        DB::table('tasks')->insert([
            'maintenance_request_id' => $mr2Id,
            'assigned_to' => $caretakerUser,
            'task_description' => 'Replace kitchen faucet',
            'priority' => 'medium',
            'status' => 'assigned',
            'started_at' => null,
            'completed_at' => null,
            'completion_notes' => null,
            'is_completed_by_caretaker' => false,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        DB::table('daily_activity_logs')->insert([
            'caretaker_id' => $caretakerUser,
            'log_date' => Carbon::now()->subDays(1),
            'activities_performed' => "1. Inspected Unit A1 for water leakage\n2. Ordered replacement faucet for Unit C2\n3. Cleaned common areas\n4. Checked electrical systems",
            'notes' => 'All units are in good condition except the reported issues.',
            'submitted_at' => Carbon::now()->subDays(1),
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        DB::table('daily_activity_logs')->insert([
            'caretaker_id' => $caretakerUser,
            'log_date' => Carbon::now()->subDays(2),
            'activities_performed' => "1. Responded to maintenance requests\n2. Cleaned the garden\n3. Checked security systems\n4. Prepared rental payment report",
            'notes' => 'Security systems are functioning properly.',
            'submitted_at' => Carbon::now()->subDays(2),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $settings = [
            ['setting_key' => 'company_name', 'setting_value' => 'Greenview Properties Ltd', 'description' => 'Company name'],
            ['setting_key' => 'company_email', 'setting_value' => 'info@greenviewproperties.com', 'description' => 'Company email'],
            ['setting_key' => 'company_phone', 'setting_value' => '+254700000000', 'description' => 'Company phone'],
            ['setting_key' => 'rent_due_day', 'setting_value' => '5', 'description' => 'Rent due day of the month'],
            ['setting_key' => 'late_fee_percentage', 'setting_value' => '5', 'description' => 'Late fee percentage'],
            ['setting_key' => 'maintenance_budget', 'setting_value' => '50000', 'description' => 'Monthly maintenance budget'],
            ['setting_key' => 'currency', 'setting_value' => 'KES', 'description' => 'Currency code'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                'setting_key' => $setting['setting_key'],
                'setting_value' => $setting['setting_value'],
                'setting_group' => 'general',
                'setting_type' => 'string',
                'is_public' => false,
                'description' => $setting['description'],
                'created_by' => $adminUser,
                'updated_by' => $adminUser,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        DB::table('sessions')->insert([
            'user_id' => $tenantUser1,
            'session_token' => 'demo_token_'.uniqid(),
            'device_type' => 'web',
            'login_time' => Carbon::now()->subHours(2),
            'logout_time' => null,
            'is_active' => true,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('sessions')->insert([
            'user_id' => $caretakerUser,
            'session_token' => 'demo_token_'.uniqid(),
            'device_type' => 'mobile',
            'login_time' => Carbon::now()->subHours(5),
            'logout_time' => Carbon::now()->subHours(3),
            'session_duration' => 120,
            'is_active' => false,
            'ip_address' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $activities = [
            ['user_id' => $tenantUser1, 'action' => 'rent_payment', 'entity_type' => 'payment', 'entity_id' => 1, 'details' => 'Paid rent amount 25000'],
            ['user_id' => $tenantUser1, 'action' => 'maintenance_request', 'entity_type' => 'maintenance_requests', 'entity_id' => $mr1Id, 'details' => 'Submitted water leakage request'],
            ['user_id' => $caretakerUser, 'action' => 'task_assigned', 'entity_type' => 'tasks', 'entity_id' => 1, 'details' => 'Assigned to fix water leakage'],
            ['user_id' => $landlordUser, 'action' => 'maintenance_approved', 'entity_type' => 'maintenance_requests', 'entity_id' => $mr1Id, 'details' => 'Approved major maintenance request'],
            ['user_id' => $adminUser, 'action' => 'system_config', 'entity_type' => 'settings', 'entity_id' => 1, 'details' => 'Updated settings'],
        ];

        foreach ($activities as $activity) {
            DB::table('activity_logs')->insert([
                'caretaker_id' => $caretakerUser,
                'property_id' => $propertyId1,
                'log_date' => Carbon::now()->subDays(rand(0, 10))->toDateString(),
                'log_time' => Carbon::now()->subDays(rand(0, 10)),
                'activity_type' => 'reporting',
                'description' => sprintf(
                    '%s | user:%s | %s:%s | %s',
                    $activity['action'],
                    $activity['user_id'],
                    $activity['entity_type'],
                    $activity['entity_id'],
                    $activity['details']
                ),
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays(rand(0, 10)),
                'location' => 'Nairobi, Kenya',
                'created_at' => Carbon::now()->subDays(rand(0, 10)),
                'updated_at' => Carbon::now()->subDays(rand(0, 10)),
            ]);
        }

        $notifications = [
            ['user_id' => $tenantUser1, 'title' => 'Rent Payment Confirmed', 'message' => 'Your rent payment of KES 25,000 for Unit A1 has been confirmed.', 'type' => 'payment', 'link' => '/payments'],
            ['user_id' => $tenantUser1, 'title' => 'Maintenance Request Approved', 'message' => 'Your water leakage maintenance request has been approved.', 'type' => 'maintenance', 'link' => '/maintenance'],
            ['user_id' => $caretakerUser, 'title' => 'New Task Assigned', 'message' => 'You have been assigned to fix water leakage in Unit A1.', 'type' => 'task', 'link' => '/tasks'],
            ['user_id' => $landlordUser, 'title' => 'Maintenance Request Pending', 'message' => 'A major maintenance request requires your approval.', 'type' => 'maintenance', 'link' => '/maintenance'],
        ];

        foreach ($notifications as $notification) {
            DB::table('notifications')->insert([
                'user_id' => $notification['user_id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'link' => $notification['link'],
                'is_read' => rand(0, 1) == 1,
                'read_at' => rand(0, 1) == 1 ? Carbon::now()->subHours(rand(1, 24)) : null,
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
                'updated_at' => Carbon::now(),
            ]);
        }

        DB::table('reports')->insert([
            'title' => 'Payment Summary - January 2024',
            'description' => 'Monthly payment summary report.',
            'report_type' => 'payment_summary',
            'generated_by' => $adminUser,
            'date_range_start' => Carbon::create(2024, 1, 1)->toDateString(),
            'date_range_end' => Carbon::create(2024, 1, 31)->toDateString(),
            'filters' => json_encode(['month' => 'January', 'year' => '2024']),
            'data' => json_encode(['summary' => 'Payment totals for January 2024']),
            'file_path' => '/reports/payment_summary_jan_2024.pdf',
            'format' => 'pdf',
            'generated_at' => Carbon::now()->subDays(5),
            'is_scheduled' => false,
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5),
        ]);

        DB::table('reports')->insert([
            'title' => 'Maintenance Report - January 2024',
            'description' => 'Maintenance requests and statuses for January 2024.',
            'report_type' => 'maintenance_report',
            'generated_by' => $landlordUser,
            'date_range_start' => Carbon::create(2024, 1, 1)->toDateString(),
            'date_range_end' => Carbon::create(2024, 1, 31)->toDateString(),
            'filters' => json_encode(['status' => 'completed', 'date_from' => '2024-01-01', 'date_to' => '2024-01-31']),
            'data' => json_encode(['summary' => 'Maintenance work overview']),
            'file_path' => '/reports/maintenance_report_jan_2024.pdf',
            'format' => 'pdf',
            'generated_at' => Carbon::now()->subDays(3),
            'is_scheduled' => false,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        $auditTrails = [
            ['user_id' => $adminUser, 'action' => 'create', 'table_name' => 'users', 'record_id' => $adminUser, 'old_values' => null, 'new_values' => json_encode(['email' => 'admin@rental.com', 'role' => 'admin'])],
            ['user_id' => $adminUser, 'action' => 'create', 'table_name' => 'landlords', 'record_id' => $landlordUser, 'old_values' => null, 'new_values' => json_encode(['company_name' => 'Greenview Properties Ltd'])],
            ['user_id' => $caretakerUser, 'action' => 'update', 'table_name' => 'maintenance_requests', 'record_id' => $mr1Id, 'old_values' => json_encode(['status' => 'submitted']), 'new_values' => json_encode(['status' => 'in_progress'])],
        ];

        foreach ($auditTrails as $audit) {
            DB::table('audit_trails')->insert([
                'user_id' => $audit['user_id'],
                'action' => $audit['action'],
                'table_name' => $audit['table_name'],
                'record_id' => $audit['record_id'],
                'old_values' => $audit['old_values'],
                'new_values' => $audit['new_values'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Laravel Seeder',
                'created_at' => Carbon::now()->subDays(rand(0, 5)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5)),
            ]);
        }

        $this->command->info(' Login Credentials:');
        $this->command->info('Admin:      admin@rental.com     / password');
        $this->command->info('Landlord:   landlord@rental.com   / password');
        $this->command->info('Caretaker:  caretaker@rental.com  / password');
        $this->command->info('Tenant 1:   tenant1@rental.com    / password');
        $this->command->info('Tenant 2:   tenant2@rental.com    / password');
        $this->command->info('============================================');
    }
}
