<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@hospital.com',
                'password' => 'password123',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Sarah Ahmed',
                'email' => 'doctor@hospital.com',
                'password' => 'password123',
                'role' => 'doctor',
                'status' => 'active',
            ],
            [
                'name' => 'Reception Staff',
                'email' => 'reception@hospital.com',
                'password' => 'password123',
                'role' => 'receptionist',
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
