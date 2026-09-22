import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { departments, status, errors } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Departments</h1>
            {status && <p>{status}</p>}
            {errors.department && <p>{errors.department}</p>}
            <p>
                <Link href="/admin/departments/create">New department</Link>
            </p>
            <ul>
                {departments.map((d) => (
                    <li key={d.id}>
                        {d.name} · {d.queue_prefix} · {d.payment_mode} · ₦{(d.base_fee_kobo / 100).toLocaleString()} ·{' '}
                        {d.is_active ? 'active' : 'inactive'} <Link href={`/admin/departments/${d.id}/edit`}>Edit</Link>{' '}
                        <Link href={`/admin/departments/${d.id}`} method="delete" as="button">
                            Delete
                        </Link>
                    </li>
                ))}
            </ul>
        </main>
    );
}
