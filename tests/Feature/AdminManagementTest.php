<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);
    }

    public function test_admin_can_create_user_toggle_status_and_reset_password(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Dosen Uji',
            'email' => 'dosenuji@verdantlms.test',
            'identity_number' => 'NIDN9901',
            'role' => 'instructor',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $created = User::where('email', 'dosenuji@verdantlms.test')->firstOrFail();
        $this->assertTrue($created->isInstructor());
        $this->assertTrue($created->is_active);

        $this->actingAs($this->admin)
            ->patch("/admin/users/{$created->id}/toggle-status")
            ->assertRedirect();
        $this->assertFalse($created->fresh()->is_active);

        $this->actingAs($this->admin)
            ->put("/admin/users/{$created->id}/reset-password", [
                'password' => 'NewSecret123!',
                'password_confirmation' => 'NewSecret123!',
            ])
            ->assertRedirect();
    }

    public function test_admin_can_manage_academic_master_data_and_export_reports(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Sistem Informasi',
            'description' => 'Rumpun ilmu SI',
            'is_active' => true,
        ])->assertRedirect();

        $category = Category::where('name', 'Sistem Informasi')->firstOrFail();

        $this->actingAs($this->admin)->post('/admin/subjects', [
            'category_id' => $category->id,
            'code' => 'SI201',
            'name' => 'Arsitektur Enterprise',
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($this->admin)->post('/admin/periods', [
            'name' => 'Ganjil 2026/2027',
            'code' => '20261',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
            'is_active' => true,
        ])->assertRedirect();

        $student = User::factory()->create(['is_active' => true]);
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $student->roles()->attach($studentRole->id);

        $course = Course::create([
            'category_id' => $category->id,
            'instructor_id' => $this->admin->id,
            'title' => 'Enterprise Integration',
            'slug' => 'enterprise-integration',
            'code' => 'ENT2026',
            'status' => 'published',
        ]);

        Enrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 50,
            'enrolled_at' => now(),
        ]);

        $pdfResponse = $this->actingAs($this->admin)->get('/admin/reports/export-pdf');
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));

        $excelResponse = $this->actingAs($this->admin)->get('/admin/reports/export-excel');
        $excelResponse->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $excelResponse->headers->get('content-disposition'));
    }
}
