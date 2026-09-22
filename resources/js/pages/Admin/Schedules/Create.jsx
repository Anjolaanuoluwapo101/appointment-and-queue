import { useForm, usePage } from '@inertiajs/react';
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
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>New Schedule</h1>
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
        </main>
    );
}
