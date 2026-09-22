<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Hospital;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    /**
     * Bootstraps the first hospital, its admin, and the 10 preloaded
     * Nigerian departments (PRD §5, §23). No Super Admin UI exists in MVP,
     * so fresh installs seed their admin here. Re-run safe.
     */
    public function run(): void
    {
        $hospital = Hospital::firstOrCreate(
            ['name' => 'General Hospital'],
            ['email' => 'info@example.com', 'phone' => '+2348000000000', 'address' => 'Lagos, Nigeria']
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Hospital Admin',
                'password' => 'password',
                'hospital_id' => $hospital->id,
                'role' => User::ROLE_ADMIN,
            ]
        );
        $admin->update(['hospital_id' => $hospital->id, 'role' => User::ROLE_ADMIN]);

        Staff::firstOrCreate(
            ['hospital_id' => $hospital->id, 'user_id' => $admin->id],
        );

        $departments = [
            ['General OPD', 'G'],
            ['Cardiology', 'C'],
            ['Paediatrics', 'P'],
            ['Gynaecology', 'Y'],
            ['Orthopaedics', 'O'],
            ['ENT', 'N'],
            ['Ophthalmology', 'E'],
            ['Dermatology', 'D'],
            ['Dental', 'T'],
            ['Emergency', 'M'],
        ];

        foreach ($departments as [$name, $prefix]) {
            Department::firstOrCreate(
                ['hospital_id' => $hospital->id, 'name' => $name],
                [
                    'queue_prefix' => $prefix,
                    'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
                    'base_fee_kobo' => 500000,
                ]
            );
        }
    }
}
