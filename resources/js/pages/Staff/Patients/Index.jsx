import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { patients, status } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Patients</h1>
            {status && <p>{status}</p>}
            <p>
                <Link href="/staff/patients/create">Register patient</Link>
            </p>
            <ul>
                {patients.map((p) => (
                    <li key={p.id}>
                        {p.full_name} · {p.phone} · {p.email}
                    </li>
                ))}
            </ul>
        </main>
    );
}
