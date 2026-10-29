<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(UserSeeder::class);

        $this->call([
            DepartmentSeeder::class,
            DoctorSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            PrescriptionSeeder::class,
            BillSeeder::class,
        ]);

        $this->command->info('Demo data ready.');
        $this->command->info('Admin        : admin@hospital.com / password123');
        $this->command->info('Doctor       : doctor@hospital.com / password123');
        $this->command->info('Receptionist : reception@hospital.com / password123');
    }
}
