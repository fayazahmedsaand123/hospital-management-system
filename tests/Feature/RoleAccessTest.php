<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    public function test_admin_can_open_administration_modules(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('departments.index'))->assertOk();
        $this->actingAs($admin)->get(route('doctors.index'))->assertOk();
        $this->actingAs($admin)->get(route('patients.index'))->assertOk();
        $this->actingAs($admin)->get(route('bills.index'))->assertOk();
        $this->actingAs($admin)->get(route('prescriptions.index'))->assertOk();
    }

    public function test_receptionist_cannot_manage_departments(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->postJson(route('departments.store'), ['department_name' => 'Cardiology'])
            ->assertForbidden();
    }

    public function test_receptionist_cannot_access_prescriptions(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)->get(route('prescriptions.index'))->assertForbidden();
    }

    public function test_doctor_cannot_access_administration_modules(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);

        $this->actingAs($doctorUser)->get(route('departments.index'))->assertForbidden();
        $this->actingAs($doctorUser)->get(route('patients.index'))->assertForbidden();
        $this->actingAs($doctorUser)->get(route('bills.index'))->assertForbidden();
    }

    public function test_only_admins_can_delete_records(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $patient = $this->makePatient();

        $this->actingAs($doctorUser)
            ->deleteJson(route('patients.destroy', $patient))
            ->assertForbidden();

        $this->actingAs($doctorUser)
            ->deleteJson(route('doctors.destroy', $doctor))
            ->assertForbidden();
    }

    public function test_json_requests_receive_a_json_forbidden_response(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->getJson(route('prescriptions.index'))
            ->assertForbidden()
            ->assertJsonPath('message', 'You do not have permission to perform this action.');
    }
}
