import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { practitioners, status } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Practitioners</h1>
            {status && <p>{status}</p>}
            <p>
                <Link href="/admin/practitioners/create">New practitioner</Link>
            </p>
            <ul>
                {practitioners.map((p) => (
                    <li key={p.id}>
                        {p.full_name} · {p.specialisation} · {p.availability} ·{' '}
                        {p.departments.map((d) => d.name).join(', ')}{' '}
                        <Link href={`/admin/practitioners/${p.id}/edit`}>Edit</Link>
                    </li>
                ))}
            </ul>
        </main>
    );
}
