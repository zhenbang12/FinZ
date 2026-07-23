<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperuserTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_user_management_and_create_user(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@finz.app',
            'password' => bcrypt('adminpassword'),
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);

        $createResponse = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Staff User',
            'email' => 'staff@finz.app',
            'password' => 'secret123',
            'is_admin' => false,
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'staff@finz.app',
            'name' => 'New Staff User',
            'is_admin' => false,
        ]);
    }

    public function test_superuser_can_edit_and_delete_other_user(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@finz.app',
            'password' => bcrypt('adminpassword'),
            'is_admin' => true,
        ]);

        $targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@finz.app',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        // Edit target user
        $editResponse = $this->actingAs($admin)->put("/admin/users/{$targetUser->id}", [
            'name' => 'Updated User Name',
            'email' => 'target_updated@finz.app',
            'is_admin' => true,
        ]);

        $editResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated User Name',
            'email' => 'target_updated@finz.app',
            'is_admin' => true,
        ]);

        // Delete target user
        $deleteResponse = $this->actingAs($admin)->delete("/admin/users/{$targetUser->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_can_logout_and_login(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@finz.app',
            'password' => bcrypt('secret123'),
            'is_admin' => false,
        ]);

        // Logout
        $logoutRes = $this->actingAs($user)->post('/logout');
        $logoutRes->assertRedirect('/login');
        $this->assertGuest();

        // Login
        $loginRes = $this->post('/login', [
            'email' => 'test@finz.app',
            'password' => 'secret123',
        ]);
        $loginRes->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
