<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Appointment::exists()) {
            return;
        }

        $patients = Patient::pluck('id')->all();
        $doctors = Doctor::pluck('id')->all();

        $slots = [];
        for ($hour = 9; $hour <= 16; $hour++) {
            $slots[] = sprintf('%02d:00:00', $hour);
            $slots[] = sprintf('%02d:30:00', $hour);
        }

        $reasons = [
            'Chest pain evaluation', 'Routine checkup', 'Persistent headache', 'Follow-up consultation',
            'Fever and body ache', 'Knee pain', 'Child vaccination', 'Blood pressure review',
            'Skin allergy', 'Shortness of breath', 'Back pain', 'Diabetes follow-up',
        ];

        $usedSlots = [];

        $create = function (int $patientId, int $doctorId, Carbon $date, string $time, string $status, string $reason) use (&$usedSlots): void {
            $key = $doctorId.'|'.$date->format('Y-m-d').'|'.$time;

            if (isset($usedSlots[$key])) {
                return;
            }

            $usedSlots[$key] = true;

            Appointment::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_date' => $date->format('Y-m-d'),
                'appointment_time' => $time,
                'reason' => $reason,
                'status' => $status,
                'notes' => null,
            ]);
        };

        // Today's schedule.
        for ($i = 0; $i < 14; $i++) {
            $create(
                $patients[$i % count($patients)],
                $doctors[$i % count($doctors)],
                today(),
                $slots[($i * 2) % count($slots)],
                $i % 3 === 0 ? 'Confirmed' : 'Scheduled',
                $reasons[$i % count($reasons)]
            );
        }

        // Upcoming appointments.
        for ($i = 0; $i < 28; $i++) {
            $create(
                $patients[($i * 3) % count($patients)],
                $doctors[($i * 7) % count($doctors)],
                today()->addDays(($i % 14) + 1),
                $slots[($i * 3) % count($slots)],
                $i % 2 === 0 ? 'Scheduled' : 'Confirmed',
                $reasons[$i % count($reasons)]
            );
        }

        // Past history.
        for ($i = 0; $i < 65; $i++) {
            $status = match ($i % 10) {
                7 => 'Cancelled',
                9 => 'No Show',
                default => 'Completed',
            };

            $create(
                $patients[($i * 5) % count($patients)],
                $doctors[($i * 3) % count($doctors)],
                today()->subDays(($i % 45) + 1),
                $slots[($i * 5) % count($slots)],
                $status,
                $reasons[$i % count($reasons)]
            );
        }
    }
}
