<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed initial registered unit heads and officers into the database.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        $users = [
            [
                'fullname' => 'Adomako Emmanuel',
                'username' => 'headofforensic',
                'email' => 'headofforensic@nacoc.gov.gh',
                'service_id' => 'SD-101',
                'phone_number' => '0244000000',
                'is_staff' => false,
                'is_head' => true,
                'is_approved' => true,
                'unit' => 'Forensic Directorate',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Dr. Evelyn Osei',
                'username' => 'evelyn_osei',
                'email' => 'evelyn_osei@nacoc.gov.gh',
                'service_id' => 'SD-884',
                'phone_number' => '0244112233',
                'is_staff' => false,
                'is_head' => true,
                'is_approved' => false,
                'unit' => 'Evidence Management',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Eng. Michael Mensah',
                'username' => 'michael_mensah',
                'email' => 'michael_mensah@nacoc.gov.gh',
                'service_id' => 'JD-904',
                'phone_number' => '0208889900',
                'is_staff' => false,
                'is_head' => true,
                'is_approved' => false,
                'unit' => 'Digital & Physical Forensics',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Dr. K. Appiah',
                'username' => 'k_appiah',
                'email' => 'k_appiah@nacoc.gov.gh',
                'service_id' => 'SD-100',
                'phone_number' => '0244001122',
                'is_staff' => false,
                'is_head' => true,
                'is_approved' => true,
                'unit' => 'LAB Analysis',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Dr. Janet Owusu',
                'username' => 'janet_owusu',
                'email' => 'janet_owusu@nacoc.gov.gh',
                'service_id' => 'JD-332',
                'phone_number' => '0551122445',
                'is_staff' => true,
                'is_head' => false,
                'is_approved' => false,
                'unit' => 'LAB Analysis',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Insp. Eric Ofori',
                'username' => 'eric_ofori',
                'email' => 'eric_ofori@nacoc.gov.gh',
                'service_id' => 'SD-554',
                'phone_number' => '0209988776',
                'is_staff' => true,
                'is_head' => false,
                'is_approved' => false,
                'unit' => 'Crime Scene Support',
                'password' => $defaultPassword,
            ],
            [
                'fullname' => 'Chief Insp. R. Quarshie',
                'username' => 'r_quarshie',
                'email' => 'r_quarshie@nacoc.gov.gh',
                'service_id' => 'JD-441',
                'phone_number' => '0201122334',
                'is_staff' => true,
                'is_head' => false,
                'is_approved' => true,
                'unit' => 'Evidence Management',
                'password' => $defaultPassword,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['service_id' => $userData['service_id']],
                $userData
            );
        }
    }
}
