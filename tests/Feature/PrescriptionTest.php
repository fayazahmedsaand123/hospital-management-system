<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->makePatient()->id,
            'prescription_date' => today()->toDateString(),
            'diagnosis' => 'Acute viral infection',
            'instructions' => 'Rest and drink plenty of fluids.',
            'items' => [
                [
                    'medicine_name' => 'Panadol',
                    'dosage' => '500 mg',
                    'frequency' => 'Three times a day',
                    'duration' => '5 days',
                    'instructions' => 'After meals',
                ],
            ],
        ], $overrides);
    }

    public function test_doctors_create_prescriptions_under_their_own_profile(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $payload = $this->payload(['doctor_id' => $otherDoctor->id]);

        $response = $this->actingAs($doctorUser)
            ->postJson(route('prescriptions.store'), $payload)
            ->assertCreated();

        $prescription = Prescription::with('items')->findOrFail($response->json('id'));

        $this->assertSame($doctor->id, $prescription->doctor_id);
        $this->assertCount(1, $prescription->items);
        $this->assertSame('Panadol', $prescription->items[0]->medicine_name);
    }

    public function test_appointment_must_belong_to_the_selected_doctor(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $appointment = Appointment::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '09:30:00',
            'status' => 'Completed',
        ]);

        $this->actingAs($doctorUser)
            ->postJson(route('prescriptions.store'), $this->payload([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_id');
    }

    public function test_receptionists_cannot_access_prescriptions(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->postJson(route('prescriptions.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_doctors_cannot_view_another_doctors_prescription(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $prescription = Prescription::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $otherDoctor->id,
            'prescription_date' => today()->toDateString(),
            'diagnosis' => 'Migraine',
        ]);

        $this->actingAs($doctorUser)
            ->getJson(route('prescriptions.show', $prescription))
            ->assertForbidden();
    }

    public function test_a_prescription_needs_at_least_one_medicine(): void
    {
        $admin = $this->makeUser('admin');
        $doctor = $this->makeDoctor();

        $this->actingAs($admin)
            ->postJson(route('prescriptions.store'), $this->payload([
                'doctor_id' => $doctor->id,
                'items' => [],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_only_admins_can_delete_prescriptions(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $admin = $this->makeUser('admin');

        $prescription = Prescription::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'prescription_date' => today()->toDateString(),
            'diagnosis' => 'Gastritis',
        ]);

        $this->actingAs($doctorUser)
            ->deleteJson(route('prescriptions.destroy', $prescription))
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson(route('prescriptions.destroy', $prescription))
            ->assertOk();

        $this->assertDatabaseMissing('prescriptions', ['id' => $prescription->id]);
    }
}
