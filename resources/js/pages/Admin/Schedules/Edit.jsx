import { useForm, usePage } from '@inertiajs/react';
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
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Edit Schedule</h1>
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
        </main>
    );
}
