import { Link, usePage } from '@inertiajs/react';

export default function Departments() {
    const { departments } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Choose Department</h1>
            <ul>
                {departments.map((d) => (
                    <li key={d.id}>
                        <Link href={`/patient/book/${d.id}/practitioners`}>{d.name}</Link> · ₦
                        {(d.base_fee_kobo / 100).toLocaleString()} · {d.payment_mode.replaceAll('_', ' ')}
                    </li>
                ))}
            </ul>
        </main>
    );
}
