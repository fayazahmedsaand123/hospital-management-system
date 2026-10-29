<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesHospitalRecords;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesHospitalRecords, RefreshDatabase;

    public function test_financial_reports_are_restricted_to_admins_and_receptionists(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);

        $this->actingAs($receptionist)->get(route('reports.billing'))->assertOk();
        $this->actingAs($receptionist)->get(route('reports.outstanding'))->assertOk();
        $this->actingAs($doctorUser)->get(route('reports.billing'))->assertForbidden();
    }

    public function test_doctors_only_see_their_own_appointments_in_reports(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $doctor = $this->makeDoctor($doctorUser);
        $otherDoctor = $this->makeDoctor();

        $ownPatient = $this->makePatient(['patient_name' => 'Own Report Patient']);
        $otherPatient = $this->makePatient(['patient_name' => 'Other Report Patient']);

        Appointment::create([
            'patient_id' => $ownPatient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'Completed',
        ]);

        Appointment::create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '09:30:00',
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($doctorUser)->getJson(route('reports.appointments', [
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
            'ajax' => 1,
        ]));

        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('Own Report Patient', $response->json('html'));
        $this->assertStringNotContainsString('Other Report Patient', $response->json('html'));
    }

    public function test_patient_report_returns_summary_counts(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $this->makePatient(['gender' => 'Male']);
        $this->makePatient(['gender' => 'Female']);

        $response = $this->actingAs($receptionist)->getJson(route('reports.patients', ['ajax' => 1]));

        $response->assertOk()
            ->assertJsonStructure(['html', 'total', 'summary' => ['total', 'male', 'female', 'other']])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.male', 1)
            ->assertJsonPath('summary.female', 1);
    }

    public function test_doctors_cannot_view_the_patient_register_report(): void
    {
        $doctorUser = $this->makeUser('doctor');
        $this->makeDoctor($doctorUser);

        $this->actingAs($doctorUser)->get(route('reports.patients'))->assertForbidden();
    }
}
