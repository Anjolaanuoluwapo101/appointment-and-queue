import React from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import ScheduleForm from './Form';

export default function Edit() {
    const { schedule, practitioners, departments, weekdays } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        practitioner_id: schedule.practitioner_id,
        department_id: schedule.department_id,
        weekday: schedule.weekday,
        start_time: schedule.start_time.slice(0, 5),
        end_time: schedule.end_time.slice(0, 5),
        slot_duration_minutes: schedule.slot_duration_minutes,
        max_per_slot: schedule.max_per_slot,
        break_start: schedule.break_start?.slice(0, 5) ?? '',
        break_end: schedule.break_end?.slice(0, 5) ?? '',
        is_active: schedule.is_active,
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/schedules/${schedule.id}`);
    };

    return (
        <AppLayout activeRoute="admin.schedules">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">Edit Schedule</h1>
                        <p className="text-xs text-zinc-500 mt-1">Modify working hours or slot capacity for this schedule entry.</p>
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
                        <CardTitle className="text-sm font-semibold">Schedule Details</CardTitle>
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
                            schedule={schedule}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
