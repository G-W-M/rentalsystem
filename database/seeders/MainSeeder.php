<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MainSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This master seeder creates comprehensive test data including:
     * - 10 Users (1 admin, 2 landlords, 3 caretakers, 5 tenants) all with @rental.com emails
     * - 3 properties with 1-3 units each
     * - 6 months of rent payments with varying amounts
     * - 6 maintenance requests with various statuses
     * - 12 days of daily activity logs
     * - Complete activity logs, notifications, reports, and audit trails
     */
    public function run(): void
    {
        // Disable foreign key checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear all tables
        $this->clearTables();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');



        // ============================================
        // SECTION 1: USERS (10 users total)
        // ============================================

        $userIds = $this->createUsers();
        $adminUser = $userIds['admin'];
        $landlordUsers = $userIds['landlords'];
        $caretakerUsers = $userIds['caretakers'];
        $tenantUsers = $userIds['tenants'];

        // ============================================
        // SECTION 2: ADMIN DETAILS
        // ============================================

        $this->createAdminDetails($adminUser);

        // ============================================
        // SECTION 3: LANDLORDS (2)
        // ============================================

        $this->createLandlords($landlordUsers);

        // ============================================
        // SECTION 4: CARETAKERS (3)
        // ============================================
        $this->createCaretakers($landlordUsers, $caretakerUsers);

        // ============================================
        // SECTION 5: PROPERTIES & UNITS (3 properties, 1-3 units each)
        // ============================================

        $propertyData = $this->createPropertiesAndUnits($landlordUsers);
        $propertyIds = $propertyData['property_ids'];
        $unitIds = $propertyData['unit_ids'];

        // ============================================
        // SECTION 6: TENANTS (5) & OCCUPANCIES
        // ============================================

        $this->createTenantsAndOccupancies(
            $landlordUsers,
            $tenantUsers,
            $unitIds,
            $adminUser
        );

        // ============================================
        // SECTION 7: PAYMENTS (6 months varying amounts)
        // ============================================

        $this->createRentPayments($tenantUsers, $unitIds, $caretakerUsers);

        // ============================================
        // SECTION 8: MAINTENANCE REQUESTS (6 total)
        // ============================================

        $maintenanceIds = $this->createMaintenanceRequests(
            $tenantUsers,
            $unitIds,
            $propertyIds,
            $caretakerUsers,
            $landlordUsers
        );

        // ============================================
        // SECTION 9: TASKS
        // ============================================

        $this->createTasks($maintenanceIds, $caretakerUsers);

        // ============================================
        // SECTION 10: DAILY ACTIVITY LOGS (12 days)
        // ============================================

        $this->createDailyActivityLogs($caretakerUsers, $propertyIds);

        // ============================================
        // SECTION 11: ACTIVITY LOGS (detailed)
        // ============================================

        $this->createActivityLogs(
            $tenantUsers,
            $caretakerUsers,
            $propertyIds,
            $landlordUsers,
            $adminUser,
            $maintenanceIds
        );

        // ============================================
        // SECTION 12: SETTINGS
        // ============================================

        $this->createSettings($adminUser);

        // ============================================
        // SECTION 13: NOTIFICATIONS (Multiple)
        // ============================================

        $this->createNotifications($tenantUsers, $caretakerUsers, $landlordUsers);

        // ============================================
        // SECTION 14: SESSIONS
        // ============================================

        $this->createSessions($tenantUsers, $caretakerUsers, $landlordUsers);

        // ============================================
        // SECTION 15: REPORTS
        // ============================================

        $this->createReports($adminUser, $landlordUsers);

        // ============================================
        // SECTION 16: AUDIT TRAILS (Multiple)
        // ============================================

        $this->createAuditTrails($adminUser, $landlordUsers, $caretakerUsers, $maintenanceIds);

        $this->displayCredentials();
    }

    /**
     * Clear all tables in correct order to avoid foreign key constraints
     */
    private function clearTables(): void
    {
        $tables = [
            'audit_trails', 'notifications', 'reports', 'settings',
            'daily_activity_logs', 'sessions', 'activity_logs', 'tasks',
            'maintenance_requests', 'payments', 'tenant_occupancies',
            'tenants', 'units', 'properties', 'caretakers', 'admins',
            'landlords', 'personal_access_tokens', 'password_reset_tokens', 'users'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();

        }
    }


    private function createUsers(): array
    {
        $users = [];

        // 1 Admin
        $users['admin'] = DB::table('users')->insertGetId([
            'username' => 'admin',
            'email' => 'admin@rental.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '0712345678',
            'full_name' => 'System Admin',
            'role' => 'admin',
            'is_active' => true,
            'last_login' => Carbon::now()->subHours(2),
            'remember_token' => Str::random(60),
            'created_at' => Carbon::now()->subMonths(6),
            'updated_at' => Carbon::now(),
        ]);

        // 2 Landlords
        $users['landlords'] = [];
        $landlordNames = [
            ['username' => 'landlord1', 'full_name' => 'John Mwangi', 'phone' => '0723456789'],
            ['username' => 'landlord2', 'full_name' => 'Sarah Wanjiru', 'phone' => '0734567890'],
        ];

        foreach ($landlordNames as $landlord) {
            $users['landlords'][] = DB::table('users')->insertGetId([
                'username' => $landlord['username'],
                'email' => $landlord['username'] . '@rental.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => $landlord['phone'],
                'full_name' => $landlord['full_name'],
                'role' => 'landlord',
                'is_active' => true,
                'last_login' => Carbon::now()->subHours(rand(1, 12)),
                'remember_token' => Str::random(60),
                'created_at' => Carbon::now()->subMonths(rand(3, 6)),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 3 Caretakers
        $users['caretakers'] = [];
        $caretakerNames = [
            ['username' => 'caretaker1', 'full_name' => 'James Otieno', 'phone' => '0745678901'],
            ['username' => 'caretaker2', 'full_name' => 'Mary Achieng', 'phone' => '0756789012'],
            ['username' => 'caretaker3', 'full_name' => 'Peter Kiprop', 'phone' => '0767890123'],
        ];

        foreach ($caretakerNames as $caretaker) {
            $users['caretakers'][] = DB::table('users')->insertGetId([
                'username' => $caretaker['username'],
                'email' => $caretaker['username'] . '@rental.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => $caretaker['phone'],
                'full_name' => $caretaker['full_name'],
                'role' => 'caretaker',
                'is_active' => true,
                'last_login' => Carbon::now()->subHours(rand(1, 24)),
                'remember_token' => Str::random(60),
                'created_at' => Carbon::now()->subMonths(rand(2, 4)),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 5 Tenants
        $users['tenants'] = [];
        $tenantNames = [
            ['username' => 'tenant1', 'full_name' => 'Alice Njoroge', 'phone' => '0778901234'],
            ['username' => 'tenant2', 'full_name' => 'Bob Kariuki', 'phone' => '0789012345'],
            ['username' => 'tenant3', 'full_name' => 'Carol Muthoni', 'phone' => '0790123456'],
            ['username' => 'tenant4', 'full_name' => 'David Ochieng', 'phone' => '0701234567'],
            ['username' => 'tenant5', 'full_name' => 'Eve Wanjiku', 'phone' => '0712345679'],
        ];

        foreach ($tenantNames as $tenant) {
            $users['tenants'][] = DB::table('users')->insertGetId([
                'username' => $tenant['username'],
                'email' => $tenant['username'] . '@rental.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => $tenant['phone'],
                'full_name' => $tenant['full_name'],
                'role' => 'tenant',
                'is_active' => true,
                'last_login' => Carbon::now()->subHours(rand(1, 48)),
                'remember_token' => Str::random(60),
                'created_at' => Carbon::now()->subMonths(rand(1, 3)),
                'updated_at' => Carbon::now(),
            ]);
        }

        return $users;
    }

    /**
     * Create admin details
     */
    private function createAdminDetails(int $adminUser): void
    {
        DB::table('admins')->insert([
            'user_id' => $adminUser,
            'admin_level' => 1,
            'created_at' => Carbon::now()->subMonths(6),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Create landlord details for 2 landlords
     */
    private function createLandlords(array $landlordUsers): void
    {
        $landlordData = [
            [
                'company_name' => 'Greenview Properties Ltd',
                'kra_pin' => 'KRA-123456789',
                'physical_address' => 'Kilimani, Nairobi, Kenya',
                'max_properties' => 10,
            ],
            [
                'company_name' => 'Sunset Real Estate Ltd',
                'kra_pin' => 'KRA-987654321',
                'physical_address' => 'Westlands, Nairobi, Kenya',
                'max_properties' => 8,
            ]
        ];

        foreach ($landlordUsers as $index => $landlordUser) {
            DB::table('landlords')->insert([
                'user_id' => $landlordUser,
                'company_name' => $landlordData[$index]['company_name'],
                'id_number' => 'ID-' . Str::random(10),
                'kra_pin' => $landlordData[$index]['kra_pin'],
                'physical_address' => $landlordData[$index]['physical_address'],
                'is_verified' => true,
                'verification_date' => Carbon::now()->subMonths(3),
                'max_properties' => $landlordData[$index]['max_properties'],
                'registration_date' => Carbon::now()->subMonths(6),
                'created_at' => Carbon::now()->subMonths(6),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Create caretakers (3 total)
     */
    private function createCaretakers(array $landlordUsers, array $caretakerUsers): void
    {
        $caretakerData = [
            [
                'salary' => 25000.00,
                'rating' => 4.5,
                'skills' => json_encode(['plumbing', 'electrical', 'carpentry']),
                'hire_date' => Carbon::now()->subMonths(8),
            ],
            [
                'salary' => 20000.00,
                'rating' => 4.0,
                'skills' => json_encode(['cleaning', 'gardening', 'painting']),
                'hire_date' => Carbon::now()->subMonths(5),
            ],
            [
                'salary' => 18000.00,
                'rating' => 3.5,
                'skills' => json_encode(['security', 'maintenance', 'cleaning']),
                'hire_date' => Carbon::now()->subMonths(3),
            ]
        ];

        foreach ($caretakerUsers as $index => $caretakerUser) {
            // Distribute caretakers among landlords
            $landlordIndex = $index % count($landlordUsers);

            DB::table('caretakers')->insert([
                'user_id' => $caretakerUser,
                'landlord_id' => $landlordUsers[$landlordIndex],
                'id_number' => 'CT-' . Str::random(8),
                'emergency_contact' => 'Emergency Contact ' . ($index + 1),
                'emergency_phone' => '07' . rand(10000000, 99999999),
                'skills' => $caretakerData[$index]['skills'],
                'is_active' => true,
                'hire_date' => $caretakerData[$index]['hire_date'],
                'termination_date' => null,
                'rating' => $caretakerData[$index]['rating'],
                'salary' => $caretakerData[$index]['salary'],
                'created_at' => Carbon::now()->subMonths(6),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Create properties and units (3 properties, 1-3 units each)
     */
    private function createPropertiesAndUnits(array $landlordUsers): array
    {
        $properties = [
            [
                'name' => 'Greenview Apartments',
                'address' => 'Kilimani, Nairobi, Kenya',
                'property_type' => 'apartment',
                'status' => 'active',
                'description' => 'Modern apartments with excellent amenities.',
                'unit_count' => 3,
            ],
            [
                'name' => 'Sunset Villas',
                'address' => 'Westlands, Nairobi, Kenya',
                'property_type' => 'apartment',
                'status' => 'active',
                'description' => 'Luxury villas with stunning sunset views.',
                'unit_count' => 2,
            ],
            [
                'name' => 'Riverside Gardens',
                'address' => 'Riverside Drive, Nairobi, Kenya',
                'property_type' => 'apartment',
                'status' => 'active',
                'description' => 'Serene gardens with river views.',
                'unit_count' => 1,
            ],
        ];

        $propertyIds = [];
        $unitIds = [];

        foreach ($properties as $index => $property) {
            // Distribute properties among landlords
            $landlordIndex = $index % count($landlordUsers);

            $propertyId = DB::table('properties')->insertGetId([
                'landlord_id' => $landlordUsers[$landlordIndex],
                'name' => $property['name'],
                'address' => $property['address'],
                'property_type' => $property['property_type'],
                'status' => $property['status'],
                'description' => $property['description'],
                'created_at' => Carbon::now()->subMonths(6),
                'updated_at' => Carbon::now(),
            ]);
            $propertyIds[] = $propertyId;

            // Create 1-3 units per property
            $unitCount = $property['unit_count'];
            $unitPrefixes = ['A', 'B', 'C', 'D', 'E'];
            $rentAmounts = [20000, 25000, 30000, 35000, 40000];
            $statuses = ['occupied', 'available', 'maintenance'];

            for ($i = 0; $i < $unitCount; $i++) {
                $unitId = DB::table('units')->insertGetId([
                    'property_id' => $propertyId,
                    'unit_number' => $unitPrefixes[$i % 5] . ($i + 1),
                    'rent_amount' => $rentAmounts[array_rand($rentAmounts)],
                    'status' => $statuses[$i % count($statuses)],
                    'created_at' => Carbon::now()->subMonths(6),
                    'updated_at' => Carbon::now(),
                ]);
                $unitIds[] = $unitId;
            }
        }

        return ['property_ids' => $propertyIds, 'unit_ids' => $unitIds];
    }

    /**
     * Create tenants and occupancies (5 tenants)
     */
    private function createTenantsAndOccupancies(
        array $landlordUsers,
        array $tenantUsers,
        array $unitIds,
        int $adminUser
    ): void {
        $moveInDates = [
            Carbon::now()->subMonths(6),
            Carbon::now()->subMonths(5),
            Carbon::now()->subMonths(4),
            Carbon::now()->subMonths(3),
            Carbon::now()->subMonths(2),
        ];

        $nationalities = ['Kenyan', 'Kenyan', 'Ugandan', 'Kenyan', 'Tanzanian'];
        $employmentStatuses = ['employed', 'self-employed', 'employed', 'student', 'employed'];
        $genders = ['female', 'male', 'female', 'male', 'female'];
        $employers = ['Safaricom', 'Self', 'KCB Bank', 'Student', 'Equity Bank'];

        foreach ($tenantUsers as $index => $tenantUser) {
            // Distribute tenants among landlords
            $landlordIndex = $index % count($landlordUsers);

            DB::table('tenants')->insert([
                'user_id' => $tenantUser,
                'landlord_id' => $landlordUsers[$landlordIndex],
                'id_number' => 'ID-' . Str::random(10),
                'nationality' => $nationalities[$index % count($nationalities)],
                'date_of_birth' => Carbon::now()->subYears(rand(25, 45))->toDateString(),
                'gender' => $genders[$index % count($genders)],
                'emergency_contact' => 'Emergency Contact ' . ($index + 1),
                'emergency_phone' => '07' . rand(10000000, 99999999),
                'employment_status' => $employmentStatuses[$index % count($employmentStatuses)],
                'employer_name' => $employers[$index % count($employers)],
                'employer_phone' => '07' . rand(10000000, 99999999),
                'is_active' => true,
                'moved_in_date' => $moveInDates[$index],
                'moved_out_date' => null,
                'created_at' => $moveInDates[$index],
                'updated_at' => Carbon::now(),
            ]);

            // Create occupancy
            DB::table('tenant_occupancies')->insert([
                'tenant_id' => $tenantUser,
                'unit_id' => $unitIds[$index % count($unitIds)],
                'start_date' => $moveInDates[$index],
                'end_date' => $moveInDates[$index]->copy()->addMonths(12),
                'is_current' => true,
                'rent_amount_at_start' => rand(20000, 50000),
                'lease_agreement_path' => '/leases/tenant_' . $tenantUser . '_' . Str::random(8) . '.pdf',
                'deposit_paid' => true,
                'deposit_amount' => rand(20000, 50000),
                'deposit_refunded' => false,
                'termination_reason' => null,
                'created_by' => $adminUser,
                'created_at' => $moveInDates[$index],
                'updated_at' => $moveInDates[$index],
            ]);
        }
    }

    /**
     * Create 6 months of rent payments with varying amounts
     */
    private function createRentPayments(
        array $tenantUsers,
        array $unitIds,
        array $caretakerUsers
    ): void {
        $paymentMethods = ['mpesa', 'bank', 'cash', 'mpesa', 'bank', 'mpesa'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'pending', 'failed'];

        foreach ($tenantUsers as $tenantIndex => $tenantUser) {
            // Get the unit for this tenant
            $occupancy = DB::table('tenant_occupancies')
                ->where('tenant_id', $tenantUser)
                ->where('is_current', true)
                ->first();

            if (!$occupancy) continue;

            $baseRent = $occupancy->rent_amount_at_start;
            $startDate = Carbon::parse($occupancy->start_date);
            $caretakerUser = $caretakerUsers[$tenantIndex % count($caretakerUsers)];

            // Generate 6 months of payments with varying amounts
            for ($month = 0; $month < 6; $month++) {
                $paymentDate = $startDate->copy()->addMonths($month);
                $dueDate = $paymentDate->copy()->addDays(5);

                // Vary the rent amount slightly each month
                $rentAmount = $baseRent + rand(-2000, 2000);
                if ($rentAmount < 10000) $rentAmount = 10000;

                // Make some payments late
                if ($month % 3 == 0) {
                    $paymentDate = $paymentDate->addDays(rand(2, 7));
                }

                // Random status distribution
                $status = $statuses[array_rand($statuses)];
                if ($month == 5) {
                    $status = 'pending'; // Last month pending
                }

                DB::table('payments')->insert([
                    'tenant_id' => $tenantUser,
                    'unit_id' => $occupancy->unit_id,
                    'amount' => $rentAmount,
                    'payment_date' => $status === 'completed' ? $paymentDate : null,
                    'due_date' => $dueDate,
                    'payment_method' => $status === 'completed' ? $paymentMethods[array_rand($paymentMethods)] : null,
                    'transaction_id' => $status === 'completed' ?
                        strtoupper(Str::random(3)) . rand(100000, 999999) : null,
                    'status' => $status,
                    'receipt_url' => $status === 'completed' ?
                        '/receipts/' . Str::random(8) . '.pdf' : null,
                    'verified_by' => $status === 'completed' ? $caretakerUser : null,
                    'verified_at' => $status === 'completed' ? $paymentDate : null,
                    'notes' => $status === 'completed' ?
                        'Rent payment for month ' . ($month + 1) . ' - KES ' . number_format($rentAmount) :
                        ($status === 'pending' ? 'Awaiting payment - KES ' . number_format($rentAmount) : 'Payment failed'),
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);
            }
        }
    }

    /**
     * Create 6 maintenance requests
     */
    private function createMaintenanceRequests(
        array $tenantUsers,
        array $unitIds,
        array $propertyIds,
        array $caretakerUsers,
        array $landlordUsers
    ): array {
        $categories = ['plumbing', 'electrical', 'structural', 'appliance', 'pest', 'security'];
        $priorities = ['low', 'medium', 'high', 'emergency'];
        $statuses = ['submitted', 'assigned', 'in_progress', 'resolved', 'rejected'];
        $requests = [
            [
                'subject' => 'Bathroom ceiling leak',
                'description' => 'Water leakage in the bathroom ceiling. Has been dripping for 3 days.',
                'category' => 'plumbing',
                'priority' => 'high',
            ],
            [
                'subject' => 'Leaking kitchen faucet',
                'description' => 'The kitchen faucet is leaking constantly and needs replacement.',
                'category' => 'plumbing',
                'priority' => 'medium',
            ],
            [
                'subject' => 'Circuit breaker tripping',
                'description' => 'Circuit keeps tripping whenever the AC is turned on.',
                'category' => 'electrical',
                'priority' => 'emergency',
            ],
            [
                'subject' => 'Broken water heater',
                'description' => 'Water heater is not producing hot water.',
                'category' => 'appliance',
                'priority' => 'high',
            ],
            [
                'subject' => 'Cracked window pane',
                'description' => 'Window has a large crack that needs urgent replacement.',
                'category' => 'structural',
                'priority' => 'medium',
            ],
            [
                'subject' => 'Termite infestation',
                'description' => 'Termites spotted in the wooden cabinets.',
                'category' => 'pest',
                'priority' => 'high',
            ],
        ];

        $maintenanceIds = [];

        foreach ($requests as $index => $request) {
            $tenantIndex = $index % count($tenantUsers);
            $unitIndex = $index % count($unitIds);
            $propertyIndex = $index % count($propertyIds);
            $caretakerIndex = $index % count($caretakerUsers);
            $landlordIndex = $index % count($landlordUsers);

            $status = $statuses[array_rand($statuses)];
            $submittedAt = Carbon::now()->subDays(rand(1, 30));
            $assignedAt = $status !== 'submitted' ? $submittedAt->copy()->addDays(rand(1, 3)) : null;
            $resolvedAt = $status === 'resolved' ? $assignedAt->copy()->addDays(rand(2, 7)) : null;

            $maintenanceId = DB::table('maintenance_requests')->insertGetId([
                'tenant_id' => $tenantUsers[$tenantIndex],
                'unit_id' => $unitIds[$unitIndex],
                'property_id' => $propertyIds[$propertyIndex],
                'category' => $request['category'],
                'subject' => $request['subject'],
                'description' => $request['description'],
                'priority' => $request['priority'],
                'status' => $status,
                'is_major' => rand(0, 1),
                'cost_estimate' => rand(1000, 50000),
                'actual_cost' => $status === 'resolved' ? rand(1000, 45000) : null,
                'before_photo' => $status !== 'submitted' ? '/photos/before_' . Str::random(8) . '.jpg' : null,
                'after_photo' => $status === 'resolved' ? '/photos/after_' . Str::random(8) . '.jpg' : null,
                'assigned_to' => $status !== 'submitted' ? $caretakerUsers[$caretakerIndex] : null,
                'submitted_at' => $submittedAt,
                'assigned_at' => $assignedAt,
                'resolved_at' => $resolvedAt,
                'approved_by_landlord' => rand(0, 1),
                'approved_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 10)) : null,
                'resolution_notes' => $status === 'resolved' ?
                    'Repaired successfully. New parts installed.' : null,
                'created_at' => $submittedAt,
                'updated_at' => $resolvedAt ?? $assignedAt ?? $submittedAt,
            ]);

            $maintenanceIds[] = $maintenanceId;
        }

        return $maintenanceIds;
    }

    /**
     * Create tasks for maintenance
     */
    private function createTasks(array $maintenanceIds, array $caretakerUsers): void
    {
        $taskStatuses = ['assigned', 'in_progress', 'completed', 'cancelled'];
        $taskDescriptions = [
            'Fix water leakage in bathroom',
            'Replace kitchen faucet',
            'Fix circuit breaker',
            'Replace water heater',
            'Replace window pane',
            'Pest control treatment',
        ];

        foreach ($maintenanceIds as $index => $maintenanceId) {
            $caretakerIndex = $index % count($caretakerUsers);
            $status = $taskStatuses[array_rand($taskStatuses)];
            $startedAt = $status !== 'assigned' ? Carbon::now()->subDays(rand(1, 5)) : null;
            $completedAt = $status === 'completed' ? $startedAt->copy()->addDays(rand(1, 3)) : null;

            DB::table('tasks')->insert([
                'maintenance_request_id' => $maintenanceId,
                'assigned_to' => $caretakerUsers[$caretakerIndex],
                'assigned_by' => $caretakerUsers[$caretakerIndex],
                'task_description' => $taskDescriptions[$index % count($taskDescriptions)],
                'priority' => ['low', 'medium', 'high', 'emergency'][array_rand(['low', 'medium', 'high', 'emergency'])],
                'due_date' => Carbon::now()->addDays(rand(1, 14)),
                'status' => $status,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'completion_notes' => $status === 'completed' ?
                    'Task completed successfully.' : null,
                'completion_photo' => $status === 'completed' ?
                    '/photos/completion_' . Str::random(8) . '.jpg' : null,
                'tenant_confirmed' => rand(0, 1),
                'is_completed_by_caretaker' => $status === 'completed' ? 1 : 0,
                'created_at' => Carbon::now()->subDays(rand(1, 10)),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Create 12 days of daily activity logs
     */
    private function createDailyActivityLogs(array $caretakerUsers, array $propertyIds): void
    {
        $activities = [
            [
                'activities' => "1. Conducted morning inspection of all units\n2. Cleaned common areas\n3. Checked security systems\n4. Responded to maintenance requests",
                'notes' => 'All systems functioning properly.'
            ],
            [
                'activities' => "1. Repaired plumbing in Unit A1\n2. Inspected electrical systems\n3. Updated maintenance logs\n4. Communicated with tenants",
                'notes' => 'Plumbing repair completed.'
            ],
            [
                'activities' => "1. Completed routine maintenance\n2. Checked fire extinguishers\n3. Cleaned parking area\n4. Prepared weekly report",
                'notes' => 'Weekly inspection completed.'
            ],
            [
                'activities' => "1. Followed up on pending maintenance requests\n2. Conducted safety checks\n3. Updated inventory\n4. Assisted with move-in inspection",
                'notes' => 'All safety checks passed.'
            ],
            [
                'activities' => "1. Emergency response to water leak\n2. Coordinated with plumber\n3. Cleaned affected area\n4. Documented incident",
                'notes' => 'Emergency resolved.'
            ],
            [
                'activities' => "1. Routine inspection of electrical systems\n2. Replaced light bulbs\n3. Checked backup generators\n4. Updated maintenance schedule",
                'notes' => 'All systems operational.'
            ],
            [
                'activities' => "1. Tenant meeting regarding maintenance\n2. Collected rent receipts\n3. Inspected vacant units\n4. Updated property files",
                'notes' => 'Meeting productive.'
            ],
            [
                'activities' => "1. Landscaping and gardening\n2. Cleaned common areas\n3. Checked pest control\n4. Prepared maintenance budget report",
                'notes' => 'Gardening completed.'
            ],
            [
                'activities' => "1. Inspected AC units\n2. Cleaned filters\n3. Checked thermostat settings\n4. Reported issues to landlord",
                'notes' => 'AC maintenance completed.'
            ],
            [
                'activities' => "1. Followed up on maintenance requests\n2. Inspected units\n3. Updated records\n4. Prepared weekly report",
                'notes' => 'All requests addressed.'
            ],
            [
                'activities' => "1. Cleaning driveways\n2. Inspected security cameras\n3. Checked gate locks\n4. Prepared security report",
                'notes' => 'Security systems functional.'
            ],
            [
                'activities' => "1. Repaired fence\n2. Cleaned common areas\n3. Updated maintenance records\n4. Communicated with landlord",
                'notes' => 'Fence repaired.'
            ],
        ];

        // Generate 12 days of logs
        for ($day = 0; $day < 12; $day++) {
            $logDate = Carbon::now()->subDays(11 - $day);
            $caretakerIndex = $day % count($caretakerUsers);
            $caretakerId = $caretakerUsers[$caretakerIndex];
            $activity = $activities[$day % count($activities)];
            $propertyId = $propertyIds[$day % count($propertyIds)];

            DB::table('daily_activity_logs')->insert([
                'caretaker_id' => $caretakerId,
                'log_date' => $logDate->toDateString(),
                'activities_performed' => $activity['activities'],
                'notes' => $activity['notes'] . ' (Property: ' . ($day % 3 + 1) . ')',
                'submitted_at' => $logDate->copy()->addHours(rand(8, 17)),
                'created_at' => $logDate,
                'updated_at' => $logDate,
            ]);
        }
    }

    /**
     * Create detailed activity logs
     */
    private function createActivityLogs(
        array $tenantUsers,
        array $caretakerUsers,
        array $propertyIds,
        array $landlordUsers,
        int $adminUser,
        array $maintenanceIds
    ): void {
        $activityTypes = ['inspection', 'cleaning', 'repair', 'meeting', 'reporting', 'other'];
        $statuses = ['draft', 'submitted', 'reviewed'];
        $locations = ['Nairobi, Kenya', 'Kilimani', 'Westlands', 'Riverside Drive', 'Karen', 'Langata'];
        $descriptions = [
            'Rent payment submitted and verified',
            'Maintenance request created and assigned',
            'Property inspection completed',
            'Tenant meeting conducted',
            'Maintenance work completed',
            'Cleaning of common areas',
            'Security system checked',
            'Gardening work done',
            'Electrical repairs completed',
            'Plumbing issues fixed',
            'Tenant complained about noise',
            'New tenant move-in inspection',
            'Security patrol conducted',
            'Maintenance budget reviewed',
            'Rent collection report generated',
            'Maintenance request approved',
            'Task assigned to caretaker',
            'Payment verification completed',
            'Property status updated',
            'Emergency repair conducted',
        ];

        // Generate 20-25 activity logs
        for ($i = 0; $i < 25; $i++) {
            $logDate = Carbon::now()->subDays(rand(0, 30));
            $userType = ['tenant', 'caretaker', 'landlord', 'admin'][rand(0, 3)];

            switch ($userType) {
                case 'tenant':
                    $userId = $tenantUsers[array_rand($tenantUsers)];
                    break;
                case 'caretaker':
                    $userId = $caretakerUsers[array_rand($caretakerUsers)];
                    break;
                case 'landlord':
                    $userId = $landlordUsers[array_rand($landlordUsers)];
                    break;
                default:
                    $userId = $adminUser;
            }

            $activityType = $activityTypes[array_rand($activityTypes)];
            $status = $statuses[array_rand($statuses)];

            DB::table('activity_logs')->insert([
                'caretaker_id' => $caretakerUsers[array_rand($caretakerUsers)],
                'property_id' => $propertyIds[array_rand($propertyIds)],
                'log_date' => $logDate->toDateString(),
                'log_time' => $logDate,
                'activity_type' => $activityType,
                'description' => $descriptions[array_rand($descriptions)] . ' | user:' . $userId,
                'duration_minutes' => rand(15, 240),
                'status' => $status,
                'submitted_at' => $status !== 'draft' ? $logDate->copy()->addHours(rand(1, 8)) : null,
                'reviewed_by' => $status === 'reviewed' ? $landlordUsers[array_rand($landlordUsers)] : null,
                'reviewed_at' => $status === 'reviewed' ? $logDate->copy()->addDays(rand(1, 3)) : null,
                'location' => $locations[array_rand($locations)],
                'photo_attachment' => rand(0, 1) ? '/photos/activity_' . Str::random(8) . '.jpg' : null,
                'created_at' => $logDate,
                'updated_at' => $logDate,
            ]);
        }
    }

    /**
     * Create system settings
     */
    private function createSettings(int $adminUser): void
    {
        $settings = [
            ['setting_key' => 'company_name', 'setting_value' => 'Greenview Properties Ltd', 'description' => 'Company name'],
            ['setting_key' => 'company_email', 'setting_value' => 'info@greenviewproperties.com', 'description' => 'Company email'],
            ['setting_key' => 'company_phone', 'setting_value' => '+254700000000', 'description' => 'Company phone'],
            ['setting_key' => 'rent_due_day', 'setting_value' => '5', 'description' => 'Rent due day of the month'],
            ['setting_key' => 'late_fee_percentage', 'setting_value' => '5', 'description' => 'Late fee percentage'],
            ['setting_key' => 'maintenance_budget', 'setting_value' => '50000', 'description' => 'Monthly maintenance budget'],
            ['setting_key' => 'currency', 'setting_value' => 'KES', 'description' => 'Currency code'],
            ['setting_key' => 'site_name', 'setting_value' => 'Rental System', 'description' => 'Site name'],
            ['setting_key' => 'timezone', 'setting_value' => 'Africa/Nairobi', 'description' => 'System timezone'],
            ['setting_key' => 'date_format', 'setting_value' => 'YYYY-MM-DD', 'description' => 'Date format'],
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
    }

    /**
     * Create multiple notifications
     */
    private function createNotifications(
        array $tenantUsers,
        array $caretakerUsers,
        array $landlordUsers
    ): void {
        $notificationTypes = ['payment', 'maintenance', 'task', 'info', 'alert'];
        $titles = [
            'Rent Payment Confirmed',
            'Maintenance Request Approved',
            'New Task Assigned',
            'Maintenance Request Pending',
            'Payment Reminder',
            'Maintenance Request Created',
            'Task Completed',
            'Urgent Maintenance Required',
            'Rent Payment Overdue',
            'Property Inspection Scheduled',
        ];
        $messages = [
            'Your rent payment has been confirmed and verified.',
            'Your maintenance request has been approved by the landlord.',
            'You have been assigned a new maintenance task.',
            'A maintenance request requires your approval.',
            'Your rent payment is due in 5 days.',
            'A new maintenance request has been created.',
            'A maintenance task has been completed.',
            'Urgent maintenance is required for your unit.',
            'Your rent payment is overdue. Please make payment immediately.',
            'A property inspection has been scheduled for next week.',
        ];

        // Create notifications for all users
        $allUsers = array_merge(
            $tenantUsers,
            $caretakerUsers,
            $landlordUsers
        );

        foreach ($allUsers as $userId) {
            // Give each user 3-6 notifications
            $notificationCount = rand(3, 6);
            for ($i = 0; $i < $notificationCount; $i++) {
                $typeIndex = array_rand($notificationTypes);
                $isRead = rand(0, 1);
                DB::table('notifications')->insert([
                    'user_id' => $userId,
                    'title' => $titles[array_rand($titles)],
                    'message' => $messages[array_rand($messages)],
                    'type' => $notificationTypes[$typeIndex],
                    'link' => '/' . ($notificationTypes[$typeIndex] === 'payment' ? 'payments' : 'maintenance'),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? Carbon::now()->subHours(rand(1, 48)) : null,
                    'created_at' => Carbon::now()->subHours(rand(1, 72)),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Create sessions
     */
    private function createSessions(
        array $tenantUsers,
        array $caretakerUsers,
        array $landlordUsers
    ): void {
        $allUsers = array_merge($tenantUsers, $caretakerUsers, $landlordUsers);
        $deviceTypes = ['web', 'mobile', 'api'];
        $locations = ['Nairobi, Kenya', 'Mombasa, Kenya', 'Kisumu, Kenya', 'Eldoret, Kenya'];

        foreach ($allUsers as $userId) {
            // Each user has 1-3 sessions
            $sessionCount = rand(1, 3);
            for ($i = 0; $i < $sessionCount; $i++) {
                $loginTime = Carbon::now()->subHours(rand(1, 24));
                $logoutTime = rand(0, 1) ? $loginTime->copy()->addHours(rand(1, 8)) : null;
                $isActive = $logoutTime === null;

                DB::table('sessions')->insert([
                    'user_id' => $userId,
                    'session_token' => Str::random(60),
                    'device_type' => $deviceTypes[array_rand($deviceTypes)],
                    'login_time' => $loginTime,
                    'logout_time' => $logoutTime,
                    'session_duration' => $logoutTime ? $loginTime->diffInMinutes($logoutTime) : null,
                    'payload' => json_encode(['user_id' => $userId]),
                    'is_active' => $isActive,
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => [
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
                        'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36',
                        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    ][array_rand([
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
                        'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36',
                        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
                    ])],
                    'location' => $locations[array_rand($locations)],
                    'last_activity' => $loginTime->timestamp,
                    'created_at' => $loginTime,
                    'updated_at' => $loginTime,
                ]);
            }
        }
    }

    /**
     * Create multiple reports
     */
    private function createReports(int $adminUser, array $landlordUsers): void
    {
        $reportTypes = ['financial', 'maintenance', 'payment_summary', 'occupancy', 'revenue'];
        $titles = [
            'Financial Summary - Monthly Report',
            'Maintenance Report - Monthly Overview',
            'Payment Summary - Monthly Report',
            'Occupancy Report - Monthly',
            'Revenue Report - Monthly',
        ];
        $descriptions = [
            'Monthly financial summary for all properties.',
            'Monthly maintenance requests and statuses.',
            'Monthly payment summary report.',
            'Monthly occupancy report for all units.',
            'Monthly revenue report by property.',
        ];

        // Generate reports for the last 3 months
        for ($month = 0; $month < 3; $month++) {
            $reportDate = Carbon::now()->subMonths($month);
            $startDate = $reportDate->copy()->startOfMonth();
            $endDate = $reportDate->copy()->endOfMonth();

            // Alternate between admin and landlords generating reports
            $generatedBy = $month % 2 == 0 ? $adminUser : $landlordUsers[$month % count($landlordUsers)];

            DB::table('reports')->insert([
                'title' => $titles[$month % count($titles)] . ' - ' . $startDate->format('F Y'),
                'description' => $descriptions[$month % count($descriptions)],
                'report_type' => $reportTypes[$month % count($reportTypes)],
                'generated_by' => $generatedBy,
                'date_range_start' => $startDate->toDateString(),
                'date_range_end' => $endDate->toDateString(),
                'filters' => json_encode([
                    'month' => $startDate->format('F'),
                    'year' => $startDate->format('Y'),
                    'status' => ['completed', 'pending']
                ]),
                'data' => json_encode([
                    'total_payments' => rand(50000, 200000),
                    'total_maintenance' => rand(5, 20),
                    'occupancy_rate' => rand(70, 95),
                    'revenue' => rand(100000, 500000),
                    'properties' => rand(1, 3),
                    'units' => rand(3, 9),
                ]),
                'file_path' => '/reports/' . Str::slug($titles[$month % count($titles)]) . '_' . $startDate->format('Y_m') . '.pdf',
                'format' => ['pdf', 'excel', 'csv'][array_rand(['pdf', 'excel', 'csv'])],
                'generated_at' => $endDate,
                'is_scheduled' => $month % 2 == 0,
                'schedule_frequency' => $month % 2 == 0 ? ['daily', 'weekly', 'monthly'][array_rand(['daily', 'weekly', 'monthly'])] : null,
                'created_at' => $endDate,
                'updated_at' => $endDate,
            ]);
        }

        // Add some extra reports for variety
        for ($i = 0; $i < 3; $i++) {
            $reportDate = Carbon::now()->subDays(rand(5, 20));
            DB::table('reports')->insert([
                'title' => 'Quick Report - ' . $reportDate->format('Y-m-d'),
                'description' => 'Ad-hoc report generated for review.',
                'report_type' => $reportTypes[array_rand($reportTypes)],
                'generated_by' => $landlordUsers[array_rand($landlordUsers)],
                'date_range_start' => $reportDate->copy()->startOfWeek()->toDateString(),
                'date_range_end' => $reportDate->toDateString(),
                'filters' => json_encode(['custom' => true, 'reason' => 'Review']),
                'data' => json_encode([
                    'summary' => 'Quick review data',
                    'total' => rand(10000, 100000),
                ]),
                'file_path' => '/reports/quick_report_' . $reportDate->format('Y_m_d') . '.pdf',
                'format' => 'pdf',
                'generated_at' => $reportDate,
                'is_scheduled' => false,
                'created_at' => $reportDate,
                'updated_at' => $reportDate,
            ]);
        }
    }

    /**
     * Create multiple audit trails
     */
    private function createAuditTrails(
        int $adminUser,
        array $landlordUsers,
        array $caretakerUsers,
        array $maintenanceIds
    ): void {
        $actions = ['create', 'update', 'delete', 'view', 'approve', 'reject'];
        $tables = ['users', 'properties', 'units', 'payments', 'maintenance_requests', 'tenants', 'settings'];
        $allUsers = array_merge([$adminUser], $landlordUsers, $caretakerUsers);

        // Generate 40-50 audit trails
        for ($i = 0; $i < 45; $i++) {
            $userId = $allUsers[array_rand($allUsers)];
            $action = $actions[array_rand($actions)];
            $table = $tables[array_rand($tables)];
            $recordId = rand(1, 50);

            // Make some actions related to specific records
            if ($table === 'maintenance_requests' && !empty($maintenanceIds)) {
                $recordId = $maintenanceIds[array_rand($maintenanceIds)];
            }

            $oldValues = null;
            $newValues = null;

            if ($action === 'update' || $action === 'approve' || $action === 'reject') {
                $oldValues = json_encode([
                    'status' => ['pending', 'submitted', 'in_progress'][array_rand(['pending', 'submitted', 'in_progress'])],
                    'updated_at' => Carbon::now()->subDays(rand(1, 5))->toDateTimeString()
                ]);
                $newValues = json_encode([
                    'status' => ['approved', 'completed', 'rejected'][array_rand(['approved', 'completed', 'rejected'])],
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'updated_by' => $userId
                ]);
            } elseif ($action === 'create') {
                $newValues = json_encode([
                    'status' => 'active',
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'created_by' => $userId
                ]);
            } elseif ($action === 'delete') {
                $oldValues = json_encode([
                    'status' => 'active',
                    'deleted_at' => null
                ]);
                $newValues = json_encode([
                    'status' => 'deleted',
                    'deleted_at' => Carbon::now()->toDateTimeString(),
                    'deleted_by' => $userId
                ]);
            }

            DB::table('audit_trails')->insert([
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => [
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
                    'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36',
                ][array_rand([
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0) AppleWebKit/605.1.15',
                    'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36'
                ])],
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }

    /**
     * Display login credentials
     */
    private function displayCredentials(): void
    {

        $this->command->info('============================================');
        $this->command->info('Admin:      admin@rental.com        / password');
        $this->command->info('Landlord 1: landlord1@rental.com    / password');
        $this->command->info('Landlord 2: landlord2@rental.com    / password');
        $this->command->info('Caretaker 1: caretaker1@rental.com  / password');
        $this->command->info('Caretaker 2: caretaker2@rental.com  / password');
        $this->command->info('Caretaker 3: caretaker3@rental.com  / password');
        $this->command->info('Tenant 1:   tenant1@rental.com      / password');
        $this->command->info('Tenant 2:   tenant2@rental.com      / password');
        $this->command->info('Tenant 3:   tenant3@rental.com      / password');
        $this->command->info('Tenant 4:   tenant4@rental.com      / password');
        $this->command->info('Tenant 5:   tenant5@rental.com      / password');

    }
}
