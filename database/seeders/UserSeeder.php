<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'username' => 'anna',
                'fullname' => 'Anita Addo',
                'unit'     => 'Forensic Directorate',
                'is_head'  => true,
                'password' => Hash::make('password123'),
            ],
            [
                'username' => 'sam',
                'fullname' => 'Samuel Atta',
                'unit'     => 'Crime Scene Support',
                'is_head'  => true,
                'password' => Hash::make('password123'),
            ],
            [
                'username' => 'john',
                'fullname' => 'John Mensah',
                'unit'     => 'Lab Analysis',
                'is_head'  => true,
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }
    }
}
