<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    public function test_admin_can_create_a_department_through_ajax(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->postJson(route('departments.store'), [
            'department_name' => 'Cardiology',
            'description' => 'Heart and vascular care',
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonPath('message', 'Department created successfully.');
        $this->assertDatabaseHas('departments', ['department_name' => 'Cardiology']);
    }

    public function test_department_names_must_be_unique(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeDepartment(['department_name' => 'Neurology']);

        $this->actingAs($admin)->postJson(route('departments.store'), [
            'department_name' => 'Neurology',
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('department_name');
    }

    public function test_departments_with_doctors_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $department = $this->makeDepartment();
        $this->makeDoctor(null, ['department_id' => $department->id]);

        $this->actingAs($admin)
            ->deleteJson(route('departments.destroy', $department))
            ->assertStatus(422);

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_admin_can_delete_an_empty_department(): void
    {
        $admin = $this->makeUser('admin');
        $department = $this->makeDepartment();

        $this->actingAs($admin)
            ->deleteJson(route('departments.destroy', $department))
            ->assertOk();

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_receptionists_cannot_update_departments(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $department = Department::create([
            'department_name' => 'Orthopedics',
            'status' => 'active',
        ]);

        $this->actingAs($receptionist)
            ->putJson(route('departments.update', $department), [
                'department_name' => 'Orthopedics Updated',
                'status' => 'active',
            ])
            ->assertForbidden();
    }
}
