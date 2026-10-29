<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['department_name' => 'Cardiology', 'description' => 'Diagnosis and treatment of heart and blood vessel conditions.'],
            ['department_name' => 'Neurology', 'description' => 'Care for disorders of the brain, spine and nervous system.'],
            ['department_name' => 'Pediatrics', 'description' => 'Medical care for infants, children and adolescents.'],
            ['department_name' => 'Orthopedics', 'description' => 'Treatment of bones, joints, muscles and injuries.'],
            ['department_name' => 'General Medicine', 'description' => 'Primary care, general checkups and internal medicine.'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['department_name' => $department['department_name']],
                $department + ['status' => 'active']
            );
        }
    }
}
