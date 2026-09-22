import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { exceptions, status } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Schedule Exceptions</h1>
            {status && <p>{status}</p>}
            <p>
                <Link href="/admin/exceptions/create">New exception</Link>
                {' · '}
                <Link href="/admin/schedules">Schedules</Link>
            </p>
            <ul>
                {exceptions.map((x) => (
                    <li key={x.id}>
                        {x.date.slice(0, 10)} · {x.practitioner.full_name} · {x.department?.name ?? 'all departments'} · {x.type}{' '}
                        {x.reason && `(${x.reason})`}{' '}
                        <Link href={`/admin/exceptions/${x.id}`} method="delete" as="button">
                            Remove
                        </Link>
                    </li>
                ))}
            </ul>
        </main>
    );
}
