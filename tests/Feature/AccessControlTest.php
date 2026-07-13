<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

       private function makeUser(string $role): User
    {
        return User::create([
            'full_name' => ucfirst($role) . ' Test User',
            'username'  => $role . '_test_' . uniqid(),
            'email'     => $role . '_' . uniqid() . '@test.com',
            'password'  => Hash::make('password'),
            'role'      => $role,
            'is_active' => true,
        ]);
    }

    public function test_tenant_cannot_access_admin_routes(): void
    {
        $tenant = $this->makeUser('tenant');

        $response = $this->actingAs($tenant)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_tenant_cannot_access_landlord_routes(): void
    {
        $tenant = $this->makeUser('tenant');

        $response = $this->actingAs($tenant)->getJson('/api/landlord/caretakers');

        $response->assertStatus(403);
    }

    public function test_caretaker_cannot_access_admin_routes(): void
    {
        $caretaker = $this->makeUser('caretaker');

        $response = $this->actingAs($caretaker)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_landlord_cannot_access_admin_routes(): void
    {
        $landlord = $this->makeUser('landlord');

        $response = $this->actingAs($landlord)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->getJson('/api/admin/users');

        $response->assertStatus(200);
    }
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }
}
 /**
     * Creates a user directly via DB insert — no factory() needed since
     * the User model does not use HasFactory.
     */
