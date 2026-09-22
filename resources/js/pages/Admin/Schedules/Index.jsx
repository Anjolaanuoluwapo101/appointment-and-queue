import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { schedules, status, errors } = usePage().props;
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Schedules</h1>
            {status && <p>{status}</p>}
            {errors.schedule && <p>{errors.schedule}</p>}
            <p>
                <Link href="/admin/schedules/create">New schedule</Link>
                {' · '}
                <Link href="/admin/exceptions">Exceptions</Link>
            </p>
            <ul>
                {schedules.map((s) => (
                    <li key={s.id}>
                        {s.practitioner.full_name} · {s.department.name} · {days[s.weekday]} {s.start_time.slice(0, 5)}–
                        {s.end_time.slice(0, 5)} · {s.slot_duration_minutes}min · {s.max_per_slot}/slot{' '}
                        {s.is_active ? '' : '(inactive)'} <Link href={`/admin/schedules/${s.id}/edit`}>Edit</Link>{' '}
                        <Link href={`/admin/schedules/${s.id}`} method="delete" as="button">
                            Delete
                        </Link>
                    </li>
                ))}
            </ul>
        </main>
    );
}
