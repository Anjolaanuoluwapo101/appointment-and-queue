import { Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { today, appointments_today, queue_active_today, no_show_rate_30d, cancel_rate_30d, payments, refund_failures } = usePage().props;

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Admin Dashboard</h1>
            <p>{today}</p>
            <p>
                <Link href="/admin/departments">Departments</Link>
                {' · '}
                <Link href="/admin/practitioners">Practitioners</Link>
                {' · '}
                <Link href="/admin/schedules">Schedules</Link>
                {' · '}
                <Link href="/admin/exceptions">Exceptions</Link>
                {' · '}
                <Link href="/admin/reports">Reports</Link>
                {' · '}
                <Link href="/admin/audit-log">Audit log</Link>
                {' · '}
                <Link href="/admin/bulk-cancellation">Bulk cancellation</Link>
                {' · '}
                <Link href="/admin/settings">Settings</Link>
            </p>

            <h2>Today</h2>
            <ul>
                <li>Appointments: {appointments_today}</li>
                <li>Active in queue: {queue_active_today}</li>
            </ul>

            <h2>Last 30 days</h2>
            <ul>
                <li>No-show rate: {no_show_rate_30d}%</li>
                <li>Cancellation rate: {cancel_rate_30d}%</li>
            </ul>

            <h2>Payments</h2>
            <ul>
                {payments.map((p) => (
                    <li key={p.status}>
                        {p.status}: {p.total} · ₦{(p.kobo / 100).toLocaleString()}
                    </li>
                ))}
            </ul>

            <h2>Refund failures ({refund_failures.length})</h2>
            <ul>
                {refund_failures.map((f) => (
                    <li key={f.id}>
                        {f.created_at.slice(0, 16).replace('T', ' ')} · {f.payload?.reference ?? 'unknown ref'}
                    </li>
                ))}
            </ul>
        </main>
    );
}
