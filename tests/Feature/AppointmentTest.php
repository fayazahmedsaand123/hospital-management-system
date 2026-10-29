<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $this->makeDoctor()->id,
            'appointment_date' => today()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'reason' => 'Fever and headache',
            'status' => 'Scheduled',
        ], $overrides);
    }

    public function test_receptionist_can_book_an_appointment(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $payload = $this->payload();

        $this->actingAs($receptionist)
            ->postJson(route('appointments.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $payload['patient_id'],
            'doctor_id' => $payload['doctor_id'],
            'appointment_time' => '10:00:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_double_booking_the_same_doctor_and_slot_is_rejected(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeDoctor();
        $date = today()->addDays(2)->toDateString();

        $first = $this->payload([
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00',
        ]);

        $second = $this->payload([
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00',
        ]);

        $this->actingAs($receptionist)->postJson(route('appointments.store'), $first)->assertCreated();

        $this->actingAs($receptionist)
            ->postJson(route('appointments.store'), $second)
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_time');
    }

    public function test_cancelled_slots_can_be_rebooked(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeDoctor();
        $patient = $this->makePatient();
        $date = today()->addDays(2)->toDateString();

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'status' => 'Cancelled',
        ]);

        $this->actingAs($receptionist)
            ->postJson(route('appointments.store'), $this->payload([
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'appointment_time' => '10:00',
            ]))
            ->assertCreated();
    }

    public function test_unavailable_doctors_cannot_be_booked(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeDoctor(null, ['availability_status' => 'unavailable']);

        $this->actingAs($receptionist)
            ->postJson(route('appointments.store'), $this->payload(['doctor_id' => $doctor->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('doctor_id');
    }

    public function test_doctors_cannot_book_appointments(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);

        $this->actingAs($doctorUser)
            ->postJson(route('appointments.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_doctors_can_update_only_their_own_appointment_status(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $own = Appointment::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '12:00:00',
            'status' => 'Scheduled',
        ]);

        $other = Appointment::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '12:30:00',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($doctorUser)
            ->patchJson(route('appointments.status', $own), ['status' => 'Confirmed'])
            ->assertOk();

        $this->assertDatabaseHas('appointments', ['id' => $own->id, 'status' => 'Confirmed']);

        $this->actingAs($doctorUser)
            ->patchJson(route('appointments.status', $other), ['status' => 'Completed'])
            ->assertForbidden();
    }

    public function test_appointment_list_renders_for_ajax_requests(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $response = $this->actingAs($receptionist)
            ->getJson(route('appointments.index', ['ajax' => 1]));

        $response->assertOk()
            ->assertJsonStructure(['html', 'total']);
    }
}
