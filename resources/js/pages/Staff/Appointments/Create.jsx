import React from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import AsyncButton from '../../../Components/AsyncButton';

export default function Create() {
    const { phone, patient, departments, department_id, practitioner_id, date, slots, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        patient_id: patient?.id ?? '',
        department_id: department_id ?? '',
        practitioner_id: practitioner_id ?? '',
        slot_id: '',
        payment_mode: 'physical',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/staff/appointments');
    };

    return (
        <AppLayout activeRoute="staff.appointments">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">Book on Behalf</h1>
                        <p className="text-xs text-zinc-500 mt-1">Receptionist workflow to schedule appointments for registered patients.</p>
                    </div>
                    <Link
                        href="/staff/appointments"
                        className="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none"
                    >
                        Back to Appointments
                    </Link>
                </div>

                {/* Step 1: Find Patient */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">1. Search Patient by Phone or Hospital No.</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form method="get" className="flex gap-2">
                            <input
                                type="text"
                                name="phone"
                                defaultValue={phone}
                                placeholder="Enter phone number or PAT-2026-XXXXX..."
                                className="flex-1 bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                            />
                            <button
                                type="submit"
                                className="bg-zinc-900 hover:bg-zinc-800 text-white font-medium px-4 py-2 rounded-md text-xs"
                            >
                                Lookup
                            </button>
                        </form>
                        {phone !== '' && phone !== undefined && (
                            <div className="mt-3 text-xs">
                                {patient ? (
                                    <div className="p-2.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-medium flex items-center justify-between">
                                        <span>
                                            ✓ Found Patient: <span className="font-semibold">{patient.full_name}</span> ({patient.phone})
                                        </span>
                                        {patient.patient_number && (
                                            <span className="font-mono text-xs bg-emerald-100 px-2 py-0.5 rounded font-semibold text-emerald-900">
                                                {patient.patient_number}
                                            </span>
                                        )}
                                    </div>
                                ) : (
                                    <div className="p-2.5 rounded bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-between">
                                        <span>No patient record found for "{phone}".</span>
                                        <Link href="/staff/patients/create" className="underline font-semibold hover:text-amber-900">
                                            Register Patient →
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Step 2: Department & Date */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">2. Select Department & Date</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form method="get" className="space-y-3">
                            <input type="hidden" name="phone" value={phone || ''} />
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-zinc-700 mb-1">Department</label>
                                    <select
                                        name="department_id"
                                        defaultValue={department_id ?? ''}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="">Select Department...</option>
                                        {departments?.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-zinc-700 mb-1">Date</label>
                                    <input
                                        type="date"
                                        name="date"
                                        defaultValue={date ?? ''}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                className="w-full border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-900 font-medium py-2 rounded-md text-xs shadow-sm"
                            >
                                Find Open Slots
                            </button>
                        </form>
                    </CardContent>
                </Card>

                {/* Step 3: Book Slot */}
                {patient && slots && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold">3. Choose Slot & Complete Booking</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <input type="hidden" value={patient.id} />
                                <div>
                                    <label htmlFor="slot_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                                        Time Slot
                                    </label>
                                    <select
                                        id="slot_id"
                                        value={data.slot_id}
                                        onChange={(e) => setData('slot_id', Number(e.target.value))}
                                        required
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="">Select a slot...</option>
                                        {slots.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner?.full_name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors?.slot && <p className="text-xs text-rose-600 mt-1">{errors.slot}</p>}
                                </div>

                                <div className="space-y-2">
                                    <label className="block text-xs font-semibold text-zinc-700">Payment Option</label>
                                    <div className="flex gap-4">
                                        <label className="flex items-center space-x-2 text-xs text-zinc-800 cursor-pointer">
                                            <input
                                                type="radio"
                                                name="payment_mode"
                                                checked={data.payment_mode === 'physical'}
                                                onChange={() => setData('payment_mode', 'physical')}
                                                className="border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                            />
                                            <span>Pay at Hospital (Cash / POS)</span>
                                        </label>
                                        <label className="flex items-center space-x-2 text-xs text-zinc-800 cursor-pointer">
                                            <input
                                                type="radio"
                                                name="payment_mode"
                                                checked={data.payment_mode === 'online'}
                                                onChange={() => setData('payment_mode', 'online')}
                                                className="border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                            />
                                            <span>Pay Online (Paystack)</span>
                                        </label>
                                    </div>
                                </div>

                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Creating booking..."
                                    variant="primary"
                                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                                >
                                    Confirm Appointment Booking
                                </AsyncButton>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
