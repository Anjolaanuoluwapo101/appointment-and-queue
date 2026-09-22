import { Link, usePage } from '@inertiajs/react';

function Row({ a }) {
    return (
        <li>
            <Link href={`/patient/appointments/${a.id}`}>
                {a.department.name} · {a.practitioner?.full_name ?? 'Any practitioner'} · {a.scheduled_at.slice(0, 16).replace('T', ' ')}
            </Link>{' '}
            · {a.status} · {a.payment_status}
        </li>
    );
}

export default function Index() {
    const { upcoming, history, status } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>My Appointments</h1>
            {status && <p>{status}</p>}
            <p>
                <Link href="/patient/book">Book new</Link>
            </p>
            <h2>Upcoming</h2>
            <ul>
                {upcoming.map((a) => (
                    <Row key={a.id} a={a} />
                ))}
            </ul>
            <h2>History</h2>
            <ul>
                {history.map((a) => (
                    <Row key={a.id} a={a} />
                ))}
            </ul>
        </main>
    );
}
