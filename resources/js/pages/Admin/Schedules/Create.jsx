import React from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import ScheduleForm from './Form';

export default function Create() {
    const { practitioners, departments, weekdays } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        practitioner_id: '',
        department_id: '',
        weekday: 1,
        start_time: '09:00',
        end_time: '13:00',
        slot_duration_minutes: 30,
        max_per_slot: 1,
        break_start: '',
        break_end: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/schedules');
    };

    return (
        <AppLayout activeRoute="admin.schedules">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">New Schedule</h1>
                        <p className="text-xs text-zinc-500 mt-1">Configure weekly practitioner working hours and slot capacities.</p>
                    </div>
                    <Link
                        href="/admin/schedules"
                        className="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none"
                    >
                        Back to Schedules
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Schedule Configuration</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ScheduleForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            practitioners={practitioners}
                            departments={departments}
                            weekdays={weekdays}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
