<?php

namespace Tests\Feature\Concerns;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesHospitalRecords
{
    protected function makeUser(string $role = 'admin', array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.Str::lower(Str::random(8)).'@example.test',
            'password' => 'password123',
            'role' => $role,
            'status' => 'active',
        ], $overrides));
    }

    protected function makeDepartment(array $overrides = []): Department
    {
        return Department::create(array_merge([
            'department_name' => 'Department '.Str::upper(Str::random(5)),
            'description' => 'Test department',
            'status' => 'active',
        ], $overrides));
    }

    protected function makeDoctor(?User $user = null, array $overrides = []): Doctor
    {
        return Doctor::create(array_merge([
            'user_id' => $user?->id,
            'department_id' => $this->makeDepartment()->id,
            'doctor_name' => 'Dr. '.Str::upper(Str::random(5)),
            'specialization' => 'General Medicine',
            'phone' => '0300-1234567',
            'email' => 'doctor-'.Str::lower(Str::random(8)).'@example.test',
            'qualification' => 'MBBS',
            'consultation_fee' => 1500,
            'availability_status' => 'available',
        ], $overrides));
    }

    protected function makePatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'patient_code' => 'PAT-'.Str::upper(Str::random(6)),
            'patient_name' => 'Patient '.Str::upper(Str::random(5)),
            'gender' => 'Male',
            'date_of_birth' => '1990-01-01',
            'phone' => '0300-7654321',
            'email' => null,
            'address' => 'Hyderabad',
            'blood_group' => 'O+',
            'emergency_contact' => null,
        ], $overrides));
    }
}
