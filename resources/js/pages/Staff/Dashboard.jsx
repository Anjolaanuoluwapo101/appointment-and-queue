import { Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth, today, appointments, queue } = usePage().props;

    const row = (label, value) => (
        <li>
            {label}: {value ?? 0}
        </li>
    );

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Staff Dashboard</h1>
            <p>
                {auth?.user?.name} · {auth?.user?.role} · {today}
            </p>
            <p>
                <Link href="/staff/patients">Patients</Link>
                {' · '}
                <Link href="/staff/appointments">Appointments</Link>
                {' · '}
                <Link href="/staff/queue">Queue</Link>
                {' · '}
                <Link href="/staff/walk-in">Walk-in</Link>
                {' · '}
                <Link href="/staff/search">Search</Link>
                {' · '}
                <Link href="/notifications">Notifications</Link>
                {(auth?.user?.role === 'practitioner' || auth?.user?.role === 'admin') && (
                    <>
                        {' · '}
                        <Link href="/staff/my-queue">My queue</Link>
                    </>
                )}
                {auth?.user?.role === 'admin' && (
                    <>
                        {' · '}
                        <Link href="/admin/dashboard">Admin</Link>
                        {' · '}
                        <Link href="/admin/departments">Departments</Link>
                        {' · '}
                        <Link href="/admin/practitioners">Practitioners</Link>
                        {' · '}
                        <Link href="/admin/schedules">Schedules</Link>
                        {' · '}
                        <Link href="/admin/bulk-cancellation">Bulk cancellation</Link>
                        {' · '}
                        <Link href="/admin/settings">Settings</Link>
                    </>
                )}
            </p>

            <h2>Today&apos;s appointments</h2>
            <ul>
                {row('Scheduled (pending check-in)', appointments.scheduled)}
                {row('Pending clearance', appointments.pending_clearance)}
                {row('In queue', appointments.in_queue)}
                {row('Completed', appointments.completed)}
                {row('No-show', appointments.no_show)}
                {row('Cancelled', appointments.cancelled)}
            </ul>

            <h2>Today&apos;s queue</h2>
            <ul>
                {row('Waiting', queue.waiting)}
                {row('Called', queue.called)}
                {row('In consultation', queue.in_consultation)}
                {row('Skipped', queue.skipped)}
                {row('Completed', queue.completed)}
                {row('Cancelled', queue.cancelled)}
            </ul>

            <Link href="/logout" method="post" as="button">Log out</Link>
        </main>
    );
}
