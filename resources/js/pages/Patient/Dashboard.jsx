import { Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth, upcoming, unread } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Patient Dashboard</h1>
            <p>Welcome, {auth?.user?.name}.</p>
            <p>
                <Link href="/patient/profile">My profile</Link>
                {' · '}
                <Link href="/patient/book">Book appointment</Link>
                {' · '}
                <Link href="/patient/appointments">My appointments</Link>
                {' · '}
                <Link href="/notifications">Notifications{unread.length > 0 && ` (${unread.length} unread)`}</Link>
            </p>

            <h2>Upcoming</h2>
            {upcoming.length === 0 && <p>No upcoming appointments.</p>}
            <ul>
                {upcoming.map((a) => (
                    <li key={a.id}>
                        <Link href={`/patient/appointments/${a.id}`}>
                            {a.department.name} · {a.practitioner?.full_name ?? 'Any practitioner'} ·{' '}
                            {a.scheduled_at.slice(0, 16).replace('T', ' ')}
                        </Link>{' '}
                        · {a.status}
                    </li>
                ))}
            </ul>

            <h2>Latest notices</h2>
            <ul>
                {unread.map((n) => (
                    <li key={n.id}>
                        <strong>{n.title}</strong> — {n.body}
                    </li>
                ))}
            </ul>

            <Link href="/logout" method="post" as="button">Log out</Link>
        </main>
    );
}
