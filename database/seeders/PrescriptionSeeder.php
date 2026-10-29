<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        if (Prescription::exists()) {
            return;
        }

        $appointments = Appointment::with('doctor')
            ->where('status', 'Completed')
            ->orderBy('appointment_date')
            ->get();

        $diagnoses = [
            'Hypertension', 'Acute bronchitis', 'Migraine', 'Type 2 diabetes',
            'Viral fever', 'Osteoarthritis', 'Gastroenteritis', 'Allergic rhinitis',
            'Pneumonia', 'Iron deficiency anemia', 'Urinary tract infection', 'Angina pectoris',
        ];

        $medicines = [
            ['name' => 'Panadol', 'dosage' => '500 mg', 'frequency' => 'Three times a day', 'duration' => '5 days'],
            ['name' => 'Augmentin', 'dosage' => '625 mg', 'frequency' => 'Twice a day', 'duration' => '7 days'],
            ['name' => 'Omeprazole', 'dosage' => '20 mg', 'frequency' => 'Once a day', 'duration' => '14 days'],
            ['name' => 'Amlodipine', 'dosage' => '5 mg', 'frequency' => 'Once a day', 'duration' => '30 days'],
            ['name' => 'Metformin', 'dosage' => '500 mg', 'frequency' => 'Twice a day', 'duration' => '30 days'],
            ['name' => 'Ibuprofen', 'dosage' => '400 mg', 'frequency' => 'Twice a day', 'duration' => '5 days'],
            ['name' => 'Cetirizine', 'dosage' => '10 mg', 'frequency' => 'Once a day at night', 'duration' => '7 days'],
            ['name' => 'Salbutamol Inhaler', 'dosage' => '2 puffs', 'frequency' => 'As needed', 'duration' => '30 days'],
            ['name' => 'Losartan', 'dosage' => '50 mg', 'frequency' => 'Once a day', 'duration' => '30 days'],
            ['name' => 'Ceftriaxone', 'dosage' => '1 g', 'frequency' => 'Once a day (IV)', 'duration' => '5 days'],
        ];

        foreach ($appointments as $index => $appointment) {
            // Not every completed visit results in a prescription.
            if ($index % 4 === 3) {
                continue;
            }

            $prescription = Prescription::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'prescription_date' => $appointment->appointment_date,
                'diagnosis' => $diagnoses[$index % count($diagnoses)],
                'instructions' => 'Take medicines after meals. Return if symptoms worsen.',
            ]);

            $itemCount = ($index % 3) + 1;

            for ($i = 0; $i < $itemCount; $i++) {
                $medicine = $medicines[($index + $i) % count($medicines)];

                $prescription->items()->create([
                    'medicine_name' => $medicine['name'],
                    'dosage' => $medicine['dosage'],
                    'frequency' => $medicine['frequency'],
                    'duration' => $medicine['duration'],
                    'instructions' => 'After meals',
                ]);
            }
        }
    }
}
