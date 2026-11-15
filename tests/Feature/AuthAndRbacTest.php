<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/instructor/dashboard')->assertRedirect('/login');
        $this->get('/student/dashboard')->assertRedirect('/login');
    }

    public function test_student_registration_assigns_student_role_regardless_of_payload(): void
    {
        Setting::setValue('allow_registration', '1');

        $response = $this->post('/register', [
            'name' => 'Mahasiswa Baru',
            'email' => 'baru@verdantlms.test',
            'identity_number' => '20260001',
            'role' => 'admin', // Malicious attempt should be ignored
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/student/dashboard');

        $user = User::where('email', 'baru@verdantlms.test')->firstOrFail();
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isAdmin());
    }

    public function test_inactive_user_cannot_login_or_access_protected_routes(): void
    {
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);
        $user->roles()->attach($studentRole->id);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rbac_blocks_cross_role_dashboard_access(): void
    {
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $instructorRole = Role::where('name', 'instructor')->firstOrFail();

        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach($studentRole->id);

        $instructor = User::factory()->create(['is_active' => true]);
        $instructor->roles()->attach($instructorRole->id);

        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/instructor/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/student/dashboard')->assertOk();

        $this->actingAs($instructor)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($instructor)->get('/instructor/dashboard')->assertOk();
    }
}
