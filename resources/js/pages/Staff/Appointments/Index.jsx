import { Link, useForm, usePage } from '@inertiajs/react';

export default function Index() {
    const { appointments, filters, departments, status } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Today&apos;s Appointments</h1>
            {status && <p>{status}</p>}
            <p>
                <Link href="/staff/appointments/create">Book on behalf</Link>
            </p>
            <form method="get">
                <label>
                    Date
                    <input type="date" name="date" defaultValue={filters.date ?? new Date().toISOString().slice(0, 10)} />
                </label>
                <label>
                    Department
                    <select name="department_id" defaultValue={filters.department_id ?? ''}>
                        <option value="">All</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Status
                    <select name="status" defaultValue={filters.status ?? ''}>
                        <option value="">All</option>
                        {['scheduled', 'pending_clearance', 'cleared', 'in_queue', 'completed', 'cancelled', 'no_show'].map((s) => (
                            <option key={s} value={s}>
                                {s}
                            </option>
                        ))}
                    </select>
                </label>
                <button type="submit">Filter</button>
            </form>
            <ul>
                {appointments.map((a) => (
                    <li key={a.id}>
                        <Link href={`/staff/appointments/${a.id}`}>
                            {a.scheduled_at.slice(11, 16)} · {a.patient.full_name} · {a.patient.phone} · {a.department.name} ·{' '}
                            {a.practitioner?.full_name ?? '—'}
                        </Link>{' '}
                        · {a.status} · {a.payment_status}
                    </li>
                ))}
            </ul>
        </main>
    );
}
