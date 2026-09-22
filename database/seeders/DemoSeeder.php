<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Practitioner;
use App\Models\PractitionerSchedule;
use App\Models\QueueEntry;
use App\Models\QueueNumberSequence;
use App\Models\Staff;
use App\Models\User;
use App\Services\SlotGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Primary Hospital (Lagoon Specialist Hospital)
        $hospital = Hospital::firstOrCreate(
            ['name' => 'Lagoon Specialist Hospital'],
            [
                'email' => 'info@lagoonhealth.com',
                'phone' => '+23412345678',
                'address' => 'Victoria Island, Lagos, Nigeria',
                'is_active' => true,
                'paystack_secret' => 'sk_test_demo123456789',
            ]
        );

        // 2. Secondary Hospital (Multi-tenancy isolation check)
        $reddington = Hospital::firstOrCreate(
            ['name' => 'Reddington Hospital'],
            [
                'email' => 'contact@reddington.com',
                'phone' => '+23419876543',
                'address' => 'Ikeja, Lagos, Nigeria',
                'is_active' => true,
            ]
        );

        // 3. Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@lagoonhealth.com'],
            [
                'name' => 'Dr. Babatunde Alabi',
                'password' => 'password',
                'hospital_id' => $hospital->id,
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
        Staff::firstOrCreate(['hospital_id' => $hospital->id, 'user_id' => $adminUser->id]);

        // 4. Receptionists
        $receptionist1User = User::firstOrCreate(
            ['email' => 'receptionist@lagoonhealth.com'],
            [
                'name' => 'Chioma Okonkwo',
                'password' => 'password',
                'hospital_id' => $hospital->id,
                'role' => User::ROLE_RECEPTIONIST,
                'is_active' => true,
            ]
        );
        Staff::firstOrCreate(['hospital_id' => $hospital->id, 'user_id' => $receptionist1User->id]);

        $receptionist2User = User::firstOrCreate(
            ['email' => 'reception2@lagoonhealth.com'],
            [
                'name' => 'Kemi Adebayo',
                'password' => 'password',
                'hospital_id' => $hospital->id,
                'role' => User::ROLE_RECEPTIONIST,
                'is_active' => true,
            ]
        );
        Staff::firstOrCreate(['hospital_id' => $hospital->id, 'user_id' => $receptionist2User->id]);

        // 5. Departments (10 Realistic Nigerian Hospital Depts)
        $deptDefs = [
            ['General OPD', 'G', 300000, 'Room 101'],
            ['Cardiology', 'C', 1000000, 'Room 204'],
            ['Paediatrics', 'P', 500000, 'Room 108'],
            ['Obstetrics & Gynaecology', 'Y', 800000, 'Room 210'],
            ['Orthopaedics', 'O', 750000, 'Room 302'],
            ['ENT (Ear Nose Throat)', 'N', 600000, 'Room 115'],
            ['Ophthalmology', 'E', 500000, 'Room 112'],
            ['Dermatology', 'D', 700000, 'Room 218'],
            ['Dental Clinic', 'T', 650000, 'Room 105'],
            ['Emergency Care', 'M', 0, 'Triage Bay 1'],
        ];

        $departments = [];
        foreach ($deptDefs as [$dName, $prefix, $fee, $room]) {
            $dept = Department::firstOrCreate(
                ['hospital_id' => $hospital->id, 'name' => $dName],
                [
                    'queue_prefix' => $prefix,
                    'payment_mode' => Department::PAYMENT_ALLOW_BOTH,
                    'base_fee_kobo' => $fee,
                    'room_label' => $room,
                    'is_active' => true,
                ]
            );
            $departments[$dName] = $dept;
        }

        // 6. Practitioners with User accounts
        $practitionerData = [
            ['dr.chidi@lagoonhealth.com', 'Dr. Chidi Nwosu', 'Cardiology', 'Cardiology'],
            ['dr.folake@lagoonhealth.com', 'Dr. Folake Ademola', 'Paediatrics', 'Paediatrics'],
            ['dr.ibrahim@lagoonhealth.com', 'Dr. Ibrahim Bello', 'General OPD', 'General OPD'],
            ['dr.emeka@lagoonhealth.com', 'Dr. Emeka Eze', 'Orthopaedics', 'Orthopaedics'],
            ['dr.funke@lagoonhealth.com', 'Dr. Funke Akindele', 'Obstetrics & Gynaecology', 'Obstetrics & Gynaecology'],
            ['dr.tunde@lagoonhealth.com', 'Dr. Tunde Oyeneyin', 'ENT (Ear Nose Throat)', 'Otolaryngology'],
            ['dr.zainab@lagoonhealth.com', 'Dr. Zainab Ahmed', 'Ophthalmology', 'Ophthalmology'],
            ['dr.blessing@lagoonhealth.com', 'Dr. Blessing Okafor', 'Dermatology', 'Dermatology'],
            ['dr.victor@lagoonhealth.com', 'Dr. Victor Udoh', 'Dental Clinic', 'Dentistry'],
            ['dr.grace@lagoonhealth.com', 'Dr. Grace Danjuma', 'Emergency Care', 'Emergency Medicine'],
        ];

        $practitioners = [];
        foreach ($practitionerData as [$email, $pName, $deptKey, $spec]) {
            $pUser = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $pName,
                    'password' => 'password',
                    'hospital_id' => $hospital->id,
                    'role' => User::ROLE_PRACTITIONER,
                    'is_active' => true,
                ]
            );

            $practitioner = Practitioner::firstOrCreate(
                ['hospital_id' => $hospital->id, 'user_id' => $pUser->id],
                [
                    'full_name' => $pName,
                    'specialisation' => $spec,
                    'availability' => Practitioner::AVAILABILITY_ACTIVE,
                ]
            );

            if (isset($departments[$deptKey])) {
                $practitioner->departments()->syncWithoutDetaching([$departments[$deptKey]->id]);
            }

            Staff::firstOrCreate(['hospital_id' => $hospital->id, 'user_id' => $pUser->id]);
            $practitioners[$deptKey] = $practitioner;

            // Schedule
            if (isset($departments[$deptKey])) {
                foreach ([1, 2, 3, 4, 5] as $day) {
                    PractitionerSchedule::firstOrCreate(
                        [
                            'hospital_id' => $hospital->id,
                            'practitioner_id' => $practitioner->id,
                            'department_id' => $departments[$deptKey]->id,
                            'weekday' => $day,
                            'start_time' => '08:00',
                        ],
                        [
                            'end_time' => '17:00',
                            'slot_duration_minutes' => 30,
                            'max_per_slot' => 3,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        // 7. Patients (Demo Patient + 50 realistic patient records)
        $demoPatientUser = User::firstOrCreate(
            ['email' => 'patient@example.com'],
            [
                'name' => 'Tobi Adeyemi',
                'password' => 'password',
                'hospital_id' => $hospital->id,
                'role' => User::ROLE_PATIENT,
                'is_active' => true,
            ]
        );

        $demoPatient = Patient::firstOrCreate(
            ['hospital_id' => $hospital->id, 'user_id' => $demoPatientUser->id],
            [
                'full_name' => 'Tobi Adeyemi',
                'phone' => '08031234567',
                'date_of_birth' => '1990-05-14',
                'gender' => 'male',
                'address' => '15 Admiralty Way, Lekki Phase 1, Lagos',
            ]
        );

        $sampleNames = [
            'Amina Mohammed', 'Chinedu Okafor', 'Yinka Balogun', 'Ngozi Eze', 'Fatima Usman',
            'Babatunde Adeleke', 'Kemi Ogundipe', 'Emeka Nnamdi', 'Chioma Nwachukwu', 'Damilola Ogunleye',
            'Suleiman Abubakar', 'Ifeoma Okeke', 'Oluwaseun Ajayi', 'Zainab Bello', 'Kelechi Iheanacho',
            'Funmi Lawson', 'Mustapha Garba', 'Mercy Johnson', 'Gabriel Afolayan', 'Halima Ibrahim',
            'Tunde Ednut', 'Regina Daniels', 'Davido Adeleke', 'Wizkid Balogun', 'Tiwa Savage',
            'Olamide Adedeji', 'Burnaboy Ogulu', 'Asake Ololade', 'Rema Ikubor', 'Ayra Starr',
            'Phyno Nelson', 'Omah Lay', 'Simi Kosoko', 'Adekunle Gold', 'Falz TheBahdGuy',
            'Mr Eazi', 'Yemi Alade', 'Tekno Miles', 'Fireboy DML', 'Joeboy Akinfenwa',
            'Bella Shmurda', 'Zlatan Ibile', 'Seyivibes Balogun', 'Shallipopi Pluto', 'Odumodublvck Kala',
            'Runtown Douglas', 'Victony Anthony', 'Young Jonn', 'Pheelz Producer', 'Sammie Okposo',
        ];

        $patients = [$demoPatient];
        foreach ($sampleNames as $i => $sName) {
            $pUser = User::firstOrCreate(
                ['email' => 'patient_demo_'.($i + 1).'@example.com'],
                [
                    'name' => $sName,
                    'password' => 'password',
                    'hospital_id' => $hospital->id,
                    'role' => User::ROLE_PATIENT,
                    'is_active' => true,
                ]
            );

            $patient = Patient::firstOrCreate(
                ['hospital_id' => $hospital->id, 'phone' => '080'.str_pad((string) ($i + 10000000), 8, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $pUser->id,
                    'full_name' => $sName,
                    'date_of_birth' => '198'.($i % 10).'-0'.(($i % 9) + 1).'-15',
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'address' => ($i + 10).' Hospital Road, Ikeja, Lagos',
                ]
            );
            $patients[] = $patient;
        }

        // 8. Generate Appointment Slots (Past 30 Days + Today + Future 14 Days)
        $cardiology = $departments['Cardiology'];
        $cardioPractitioner = $practitioners['Cardiology'];

        $opd = $departments['General OPD'];
        $opdPractitioner = $practitioners['General OPD'];

        $paediatrics = $departments['Paediatrics'];
        $paediatricsPractitioner = $practitioners['Paediatrics'];

        // Helper to create historical completed data for analytics
        $today = Carbon::today();

        // Historical Data Generation (Past 30 Days)
        for ($daysAgo = 30; $daysAgo >= 1; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $dateStr = $date->toDateString();

            foreach ([$cardiology, $opd, $paediatrics] as $dept) {
                $p = $practitioners[$dept->name] ?? $cardioPractitioner;

                $slot = AppointmentSlot::create([
                    'hospital_id' => $hospital->id,
                    'department_id' => $dept->id,
                    'practitioner_id' => $p->id,
                    'date' => $dateStr,
                    'starts_at' => $date->copy()->setHour(9)->setMinute(0),
                    'ends_at' => $date->copy()->setHour(9)->setMinute(30),
                    'capacity' => 5,
                    'booked_count' => 3,
                    'is_active' => true,
                ]);

                // Create 3 historical completed appointments per dept per day
                for ($k = 0; $k < 3; $k++) {
                    $randPatient = $patients[($daysAgo * 3 + $k) % count($patients)];

                    $apt = Appointment::create([
                        'hospital_id' => $hospital->id,
                        'patient_id' => $randPatient->id,
                        'department_id' => $dept->id,
                        'practitioner_id' => $p->id,
                        'slot_id' => $slot->id,
                        'scheduled_at' => $date->copy()->setHour(9)->setMinute(0),
                        'status' => Appointment::STATUS_COMPLETED,
                        'payment_mode' => $k % 2 === 0 ? Appointment::PAY_MODE_ONLINE : Appointment::PAY_MODE_PHYSICAL,
                        'payment_status' => Appointment::PAY_PAID,
                        'checked_in_by' => $receptionist1User->id,
                        'checked_in_at' => $date->copy()->setHour(8)->setMinute(45),
                        'created_at' => $date->copy()->subDays(2),
                    ]);

                    $pmt = Payment::create([
                        'hospital_id' => $hospital->id,
                        'patient_id' => $randPatient->id,
                        'appointment_id' => $apt->id,
                        'provider' => Payment::PROVIDER_PAYSTACK,
                        'reference' => 'PAY-HIST-'.$apt->id.'-'.Str::random(5),
                        'amount_kobo' => $dept->base_fee_kobo,
                        'status' => Payment::STATUS_SUCCESS,
                        'verified_via_webhook' => true,
                        'paid_at' => $date->copy()->subDays(2),
                    ]);

                    $apt->update(['payment_id' => $pmt->id]);

                    QueueEntry::create([
                        'hospital_id' => $hospital->id,
                        'department_id' => $dept->id,
                        'appointment_id' => $apt->id,
                        'patient_id' => $randPatient->id,
                        'practitioner_id' => $p->id,
                        'queue_number' => $dept->queue_prefix.str_pad((string) ($k + 1), 3, '0', STR_PAD_LEFT),
                        'queue_date' => $dateStr,
                        'status' => QueueEntry::STATUS_COMPLETED,
                        'called_at' => $date->copy()->setHour(9)->setMinute(10),
                        'started_consultation_at' => $date->copy()->setHour(9)->setMinute(12),
                        'completed_at' => $date->copy()->setHour(9)->setMinute(30),
                        'action_by' => $p->user_id,
                        'created_at' => $date->copy()->setHour(8)->setMinute(45),
                    ]);
                }
            }
        }

        // 9. TODAY'S LIVE SCENE (Busy Active Morning Demo Data)
        QueueNumberSequence::create([
            'hospital_id' => $hospital->id,
            'department_id' => $cardiology->id,
            'queue_date' => $today->toDateString(),
            'last_number' => 5,
        ]);

        QueueNumberSequence::create([
            'hospital_id' => $hospital->id,
            'department_id' => $opd->id,
            'queue_date' => $today->toDateString(),
            'last_number' => 3,
        ]);

        // Today's Slots
        $cardioTodaySlot = AppointmentSlot::create([
            'hospital_id' => $hospital->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'date' => $today->toDateString(),
            'starts_at' => $today->copy()->setHour(10)->setMinute(0),
            'ends_at' => $today->copy()->setHour(10)->setMinute(30),
            'capacity' => 10,
            'booked_count' => 6,
            'is_active' => true,
        ]);

        // Today Entry 1: Tobi Adeyemi (Demo Patient) -> In Consultation (Cardiology C001)
        $apt1 = Appointment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $demoPatient->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'slot_id' => $cardioTodaySlot->id,
            'scheduled_at' => $today->copy()->setHour(10)->setMinute(0),
            'status' => Appointment::STATUS_IN_QUEUE,
            'payment_mode' => Appointment::PAY_MODE_ONLINE,
            'payment_status' => Appointment::PAY_PAID,
            'checked_in_by' => $receptionist1User->id,
            'checked_in_at' => $today->copy()->setHour(9)->setMinute(30),
        ]);

        $pmt1 = Payment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $demoPatient->id,
            'appointment_id' => $apt1->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'PAY-TODAY-001',
            'amount_kobo' => $cardiology->base_fee_kobo,
            'status' => Payment::STATUS_SUCCESS,
            'verified_via_webhook' => true,
            'paid_at' => $today->copy()->setHour(9)->setMinute(0),
        ]);
        $apt1->update(['payment_id' => $pmt1->id]);

        QueueEntry::create([
            'hospital_id' => $hospital->id,
            'department_id' => $cardiology->id,
            'appointment_id' => $apt1->id,
            'patient_id' => $demoPatient->id,
            'practitioner_id' => $cardioPractitioner->id,
            'queue_number' => 'C001',
            'queue_date' => $today->toDateString(),
            'status' => QueueEntry::STATUS_IN_CONSULTATION,
            'called_at' => $today->copy()->setHour(10)->setMinute(5),
            'started_consultation_at' => $today->copy()->setHour(10)->setMinute(7),
            'action_by' => $cardioPractitioner->user_id,
        ]);

        // Today Entry 2: Amina Mohammed -> Called to Room (Cardiology C002)
        $apt2 = Appointment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[1]->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'slot_id' => $cardioTodaySlot->id,
            'scheduled_at' => $today->copy()->setHour(10)->setMinute(0),
            'status' => Appointment::STATUS_IN_QUEUE,
            'payment_mode' => Appointment::PAY_MODE_ONLINE,
            'payment_status' => Appointment::PAY_PAID,
            'checked_in_by' => $receptionist1User->id,
            'checked_in_at' => $today->copy()->setHour(9)->setMinute(35),
        ]);

        $pmt2 = Payment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[1]->id,
            'appointment_id' => $apt2->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'PAY-TODAY-002',
            'amount_kobo' => $cardiology->base_fee_kobo,
            'status' => Payment::STATUS_SUCCESS,
            'verified_via_webhook' => true,
            'paid_at' => $today->copy()->setHour(9)->setMinute(5),
        ]);
        $apt2->update(['payment_id' => $pmt2->id]);

        QueueEntry::create([
            'hospital_id' => $hospital->id,
            'department_id' => $cardiology->id,
            'appointment_id' => $apt2->id,
            'patient_id' => $patients[1]->id,
            'practitioner_id' => $cardioPractitioner->id,
            'queue_number' => 'C002',
            'queue_date' => $today->toDateString(),
            'status' => QueueEntry::STATUS_CALLED,
            'called_at' => $today->copy()->setHour(10)->setMinute(12),
            'action_by' => $cardioPractitioner->user_id,
        ]);

        // Today Entry 3: Chinedu Okafor -> Waiting (Cardiology C003)
        $apt3 = Appointment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[2]->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'slot_id' => $cardioTodaySlot->id,
            'scheduled_at' => $today->copy()->setHour(10)->setMinute(0),
            'status' => Appointment::STATUS_IN_QUEUE,
            'payment_mode' => Appointment::PAY_MODE_ONLINE,
            'payment_status' => Appointment::PAY_PAID,
            'checked_in_by' => $receptionist1User->id,
            'checked_in_at' => $today->copy()->setHour(9)->setMinute(40),
        ]);

        $pmt3 = Payment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[2]->id,
            'appointment_id' => $apt3->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'PAY-TODAY-003',
            'amount_kobo' => $cardiology->base_fee_kobo,
            'status' => Payment::STATUS_SUCCESS,
            'verified_via_webhook' => true,
            'paid_at' => $today->copy()->setHour(9)->setMinute(10),
        ]);
        $apt3->update(['payment_id' => $pmt3->id]);

        QueueEntry::create([
            'hospital_id' => $hospital->id,
            'department_id' => $cardiology->id,
            'appointment_id' => $apt3->id,
            'patient_id' => $patients[2]->id,
            'practitioner_id' => $cardioPractitioner->id,
            'queue_number' => 'C003',
            'queue_date' => $today->toDateString(),
            'status' => QueueEntry::STATUS_WAITING,
        ]);

        // Today Entry 4: Yinka Balogun -> Pending Clearance (Reception Cash clearance modal demo)
        $apt4 = Appointment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[3]->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'slot_id' => $cardioTodaySlot->id,
            'scheduled_at' => $today->copy()->setHour(10)->setMinute(0),
            'status' => Appointment::STATUS_PENDING_CLEARANCE,
            'payment_mode' => Appointment::PAY_MODE_PHYSICAL,
            'payment_status' => Appointment::PAY_UNPAID,
            'checked_in_by' => $receptionist1User->id,
            'checked_in_at' => $today->copy()->setHour(9)->setMinute(50),
        ]);

        // 10. Failed Refund Log (For Admin Refund Inbox demo)
        $failedRefundApt = Appointment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[4]->id,
            'department_id' => $cardiology->id,
            'practitioner_id' => $cardioPractitioner->id,
            'scheduled_at' => $today->copy()->subDays(1),
            'status' => Appointment::STATUS_CANCELLED,
            'payment_mode' => Appointment::PAY_MODE_ONLINE,
            'payment_status' => Appointment::PAY_PAID,
            'cancellation_reason' => 'Patient cancelled booking due to schedule conflict',
        ]);

        $failedRefundPmt = Payment::create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patients[4]->id,
            'appointment_id' => $failedRefundApt->id,
            'provider' => Payment::PROVIDER_PAYSTACK,
            'reference' => 'PAY-REFUND-FAIL-001',
            'amount_kobo' => $cardiology->base_fee_kobo,
            'status' => Payment::STATUS_SUCCESS,
            'verified_via_webhook' => true,
            'paid_at' => $today->copy()->subDays(1),
        ]);
        $failedRefundApt->update(['payment_id' => $failedRefundPmt->id]);

        PaymentLog::create([
            'hospital_id' => $hospital->id,
            'payment_id' => $failedRefundPmt->id,
            'event' => 'refund',
            'payload' => ['error' => 'Paystack API rate limit exceeded. Manual resolution required.'],
            'result' => 'failed',
        ]);

        // 11. Audit Logs & Notification Logs (300+ entries)
        for ($i = 0; $i < 50; $i++) {
            AuditLog::create([
                'hospital_id' => $hospital->id,
                'user_id' => $adminUser->id,
                'action' => 'queue_entry_called',
                'subject_type' => QueueEntry::class,
                'subject_id' => 1,
                'before' => ['status' => QueueEntry::STATUS_WAITING],
                'after' => ['status' => QueueEntry::STATUS_CALLED],
                'created_at' => $today->copy()->subDays($i % 10),
            ]);

            NotificationLog::create([
                'hospital_id' => $hospital->id,
                'channel' => 'mail',
                'recipient' => 'patient_demo_'.($i + 1).'@example.com',
                'subject' => 'Appointment Reminder - Lagoon Specialist Hospital',
                'status' => $i % 7 === 0 ? 'failed' : 'sent',
                'error' => $i % 7 === 0 ? 'SMTP timeout connection' : null,
                'created_at' => $today->copy()->subDays($i % 10),
            ]);
        }

        // 12. Generate Future Slots automatically for the next 14 days
        app(SlotGenerator::class)->generateForHospital($hospital, 14);
    }
}
