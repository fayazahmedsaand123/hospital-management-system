<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    public function test_receptionist_can_register_a_patient_and_a_code_is_generated(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $response = $this->actingAs($receptionist)->postJson(route('patients.store'), [
            'patient_name' => 'Ali Raza',
            'gender' => 'Male',
            'date_of_birth' => '1992-04-12',
            'phone' => '0301-2345678',
            'email' => 'ali@example.test',
            'blood_group' => 'B+',
        ]);

        $response->assertCreated()->assertJsonPath('record.patient_code', 'PAT-00001');
        $this->assertDatabaseHas('patients', [
            'patient_name' => 'Ali Raza',
            'patient_code' => 'PAT-00001',
        ]);
    }

    public function test_patient_validation_rejects_missing_fields(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->postJson(route('patients.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['patient_name', 'gender', 'date_of_birth', 'phone']);
    }

    public function test_patients_with_history_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $patient = $this->makePatient();
        $doctor = $this->makeDoctor();

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('patients.destroy', $patient))
            ->assertStatus(422);
    }

    public function test_doctors_can_only_view_their_assigned_patients(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $ownPatient = $this->makePatient();
        $otherPatient = $this->makePatient();

        Appointment::create([
            'patient_id' => $ownPatient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '11:00:00',
            'status' => 'Scheduled',
        ]);

        Appointment::create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '11:30:00',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($doctorUser)->get(route('patients.show', $ownPatient))->assertOk();
        $this->actingAs($doctorUser)->get(route('patients.show', $otherPatient))->assertForbidden();
    }

    public function test_patient_lookup_returns_matching_patients(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $patient = $this->makePatient(['patient_name' => 'Zainab Khan']);
        $this->makePatient(['patient_name' => 'Someone Else']);

        $response = $this->actingAs($receptionist)
            ->getJson(route('lookups.patients', ['q' => 'Zainab']));

        $response->assertOk()
            ->assertJsonCount(1, 'patients')
            ->assertJsonPath('patients.0.id', $patient->id);
    }
}
