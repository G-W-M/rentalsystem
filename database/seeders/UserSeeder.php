<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| UserSeeder — Developer B
|--------------------------------------------------------------------------
| Seeds one user per role with password "password", plus the linked role
| profile rows (landlords/caretakers/tenants). Idempotent via updateOrCreate.
| Role-profile tables are written via the query builder so this seeder does
| not depend on Dev A's Eloquent models existing.
|
| Run: php artisan db:seed --class=UserSeeder
*/
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username'  => 'admin',
                'full_name' => 'System Admin',
                'role'      => 'admin',
                'password'  => Hash::make('password'),
                'phone'     => '0700000000',
                'is_active' => true,
            ]
        );


        $landlord = User::updateOrCreate(
            ['email' => 'landlord@example.com'],
            [
                'username'  => 'landlord',
                'full_name' => 'Jane Landlord',
                'role'      => 'landlord',
                'password'  => Hash::make('password'),
                'phone'     => '0711111111',
                'is_active' => true,
            ]
        );

        $caretaker = User::updateOrCreate(
            ['email' => 'caretaker@example.com'],
            [
                'username'  => 'caretaker',
                'full_name' => 'Carl Caretaker',
                'role'      => 'caretaker',
                'password'  => Hash::make('password'),
                'phone'     => '0722222222',
                'is_active' => true,
            ]
        );

        $tenant = User::updateOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'username'  => 'tenant',
                'full_name' => 'Tom Tenant',
                'role'      => 'tenant',
                'password'  => Hash::make('password'),
                'phone'     => '0733333333',
                'is_active' => true,
            ]
        );

        // Role-profile rows via query builder (no dependency on Dev A models).
        if (Schema::hasTable('landlords')) {
            DB::table('landlords')->updateOrInsert(
                ['user_id' => $landlord->id],
                [
                    'company_name'      => 'Jane Properties Ltd',
                    'is_verified'       => true,
                    'max_properties'    => 20,
                    'registration_date' => now(),
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );
        }

        if (Schema::hasTable('caretakers')) {
            DB::table('caretakers')->updateOrInsert(
                ['user_id' => $caretaker->id],
                [
                    'landlord_id' => $landlord->id,
                    'is_active'   => true,
                    'hire_date'   => now()->subYear()->toDateString(),
                    'skills'      => json_encode(['plumbing', 'electrical']),
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        if (Schema::hasTable('tenants')) {
            DB::table('tenants')->updateOrInsert(
                ['user_id' => $tenant->id],
                [
                    'landlord_id' => $landlord->id,
                    'is_active'   => true,
                    'nationality' => 'Kenyan',
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        $this->command?->info('Seeded 4 users (admin/landlord/caretaker/tenant); password = "password".');
    }
}
