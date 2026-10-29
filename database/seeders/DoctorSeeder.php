<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'department_name');
        $doctorUserId = User::where('email', 'doctor@hospital.com')->value('id');

        $doctors = [
            ['department' => 'Cardiology', 'doctor_name' => 'Dr. Sarah Ahmed', 'specialization' => 'Interventional Cardiology', 'phone' => '0300-1234567', 'email' => 'sarah.ahmed@hospital.com', 'qualification' => 'MBBS, FCPS (Cardiology)', 'consultation_fee' => 3000, 'availability_status' => 'available'],
            ['department' => 'Cardiology', 'doctor_name' => 'Dr. Imran Sheikh', 'specialization' => 'Clinical Cardiology', 'phone' => '0300-2345678', 'email' => 'imran.sheikh@hospital.com', 'qualification' => 'MBBS, FCPS', 'consultation_fee' => 2500, 'availability_status' => 'available'],
            ['department' => 'Neurology', 'doctor_name' => 'Dr. Ayesha Khan', 'specialization' => 'Neurology', 'phone' => '0300-3456789', 'email' => 'ayesha.khan@hospital.com', 'qualification' => 'MBBS, FCPS (Neurology)', 'consultation_fee' => 3500, 'availability_status' => 'available'],
            ['department' => 'Neurology', 'doctor_name' => 'Dr. Farhan Qureshi', 'specialization' => 'Neurophysiology', 'phone' => '0300-4567890', 'email' => 'farhan.qureshi@hospital.com', 'qualification' => 'MBBS, FCPS', 'consultation_fee' => 3200, 'availability_status' => 'available'],
            ['department' => 'Pediatrics', 'doctor_name' => 'Dr. Bilal Memon', 'specialization' => 'Pediatric Medicine', 'phone' => '0300-5678901', 'email' => 'bilal.memon@hospital.com', 'qualification' => 'MBBS, DCH', 'consultation_fee' => 2000, 'availability_status' => 'available'],
            ['department' => 'Pediatrics', 'doctor_name' => 'Dr. Rabia Malik', 'specialization' => 'Neonatology', 'phone' => '0300-6789012', 'email' => 'rabia.malik@hospital.com', 'qualification' => 'MBBS, FCPS', 'consultation_fee' => 2200, 'availability_status' => 'available'],
            ['department' => 'Orthopedics', 'doctor_name' => 'Dr. Hina Abbasi', 'specialization' => 'Orthopedic Surgery', 'phone' => '0300-7890123', 'email' => 'hina.abbasi@hospital.com', 'qualification' => 'MBBS, MS (Orthopedics)', 'consultation_fee' => 4000, 'availability_status' => 'available'],
            ['department' => 'Orthopedics', 'doctor_name' => 'Dr. Tariq Rajput', 'specialization' => 'Sports Injuries', 'phone' => '0300-8901234', 'email' => 'tariq.rajput@hospital.com', 'qualification' => 'MBBS, FCPS', 'consultation_fee' => 3800, 'availability_status' => 'unavailable'],
            ['department' => 'General Medicine', 'doctor_name' => 'Dr. Kashif Soomro', 'specialization' => 'Internal Medicine', 'phone' => '0300-9012345', 'email' => 'kashif.soomro@hospital.com', 'qualification' => 'MBBS, MD', 'consultation_fee' => 1500, 'availability_status' => 'available'],
            ['department' => 'General Medicine', 'doctor_name' => 'Dr. Sana Baloch', 'specialization' => 'Family Medicine', 'phone' => '0301-0123456', 'email' => 'sana.baloch@hospital.com', 'qualification' => 'MBBS', 'consultation_fee' => 1800, 'availability_status' => 'available'],
        ];

        foreach ($doctors as $doctor) {
            $data = $doctor;
            $data['department_id'] = $departments[$doctor['department']];
            unset($data['department']);

            if ($doctor['email'] === 'sarah.ahmed@hospital.com') {
                $data['user_id'] = $doctorUserId;
            }

            Doctor::firstOrCreate(['email' => $doctor['email']], $data);
        }
    }
}
