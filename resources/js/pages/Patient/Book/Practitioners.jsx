import { Link, usePage } from '@inertiajs/react';

export default function Practitioners() {
    const { department, practitioners } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>{department.name} — Choose Practitioner</h1>
            <p>
                <Link href={`/patient/book/${department.id}/slots`}>Skip — show all slots</Link>
            </p>
            <ul>
                {practitioners.map((p) => (
                    <li key={p.id}>
                        <Link href={`/patient/book/${department.id}/slots?practitioner_id=${p.id}`}>
                            {p.full_name} · {p.specialisation}
                        </Link>{' '}
                        · ₦{(p.fee_kobo / 100).toLocaleString()}
                        {p.bio && <p>{p.bio}</p>}
                    </li>
                ))}
            </ul>
        </main>
    );
}
