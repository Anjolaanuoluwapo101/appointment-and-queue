import React from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import AsyncButton from '../../../Components/AsyncButton';

export default function Reschedule() {
    const { appointment, slots, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({ slot_id: '' });

    const submit = (e) => {
        e.preventDefault();
        post(`/patient/appointments/${appointment.id}/reschedule`);
    };

    return (
        <AppLayout activeRoute="patient.appointments">
            <div className="max-w-xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">Reschedule Appointment</h1>
                        <p className="text-xs text-zinc-500 mt-1">Select a new open consultation slot for {appointment?.department?.name}.</p>
                    </div>
                    <Link
                        href={`/patient/appointments/${appointment.id}`}
                        className="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none"
                    >
                        Cancel & Back
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Select New Time Slot</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {errors?.slot && (
                            <div className="mb-4 p-3 rounded bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800">
                                {errors.slot}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="slot_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Available Consultation Slots
                                </label>
                                <select
                                    id="slot_id"
                                    value={data.slot_id}
                                    onChange={(e) => setData('slot_id', Number(e.target.value))}
                                    required
                                    className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                >
                                    <option value="">Select a new slot...</option>
                                    {slots?.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner?.full_name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <AsyncButton
                                type="submit"
                                loading={processing}
                                loadingText="Moving appointment..."
                                variant="primary"
                                className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                            >
                                Confirm Reschedule
                            </AsyncButton>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
