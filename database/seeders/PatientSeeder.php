<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        if (Patient::exists()) {
            return;
        }

        $firstNames = [
            'Ahmed', 'Fatima', 'Ali', 'Ayesha', 'Bilal', 'Zainab', 'Hassan', 'Hina', 'Imran', 'Sana',
            'Usman', 'Maria', 'Farhan', 'Iqra', 'Kashif', 'Rabia', 'Tariq', 'Nida', 'Salman', 'Mehwish',
            'Asad', 'Sidra', 'Rizwan', 'Huma', 'Kamran', 'Anum', 'Naveed', 'Sadia', 'Faisal', 'Komal',
            'Junaid', 'Bushra', 'Adnan', 'Shazia', 'Yasir', 'Amna', 'Shahid', 'Rida', 'Waqar', 'Saba',
        ];

        $lastNames = ['Sheikh', 'Khan', 'Memon', 'Abbasi', 'Soomro', 'Baloch', 'Qureshi', 'Malik', 'Rajput', 'Syed'];

        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        $areas = [
            'Latifabad, Hyderabad', 'Qasimabad, Hyderabad', 'Saddar, Hyderabad',
            'Gulshan-e-Iqbal, Karachi', 'Clifton, Karachi', 'Satellite Town, Quetta',
        ];

        foreach ($firstNames as $index => $firstName) {
            $gender = $index % 2 === 0 ? 'Male' : 'Female';
            $dateOfBirth = Carbon::create(1955 + ($index % 50), (($index * 3) % 12) + 1, (($index * 7) % 27) + 1);

            Patient::create([
                'patient_code' => 'PAT-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'patient_name' => $firstName.' '.$lastNames[$index % count($lastNames)],
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth->format('Y-m-d'),
                'phone' => '03'.str_pad((string) (11 + $index), 2, '0', STR_PAD_LEFT).'-'.str_pad((string) (1000000 + $index * 7919), 7, '0', STR_PAD_LEFT),
                'email' => $index % 3 === 0 ? strtolower($firstName).'.'.strtolower($lastNames[$index % 10]).'@example.com' : null,
                'address' => $areas[$index % count($areas)],
                'blood_group' => $bloodGroups[$index % count($bloodGroups)],
                'emergency_contact' => '03'.str_pad((string) (21 + $index), 2, '0', STR_PAD_LEFT).'-'.str_pad((string) (2000000 + $index * 104729), 7, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
