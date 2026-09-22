import { Link, useForm, usePage } from '@inertiajs/react';

export default function Index() {
    const { notifications, unread_count } = usePage().props;
    const read = useForm({});

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>Notifications ({unread_count} unread)</h1>
            {unread_count > 0 && (
                <button type="button" disabled={read.processing} onClick={() => read.post('/notifications/read-all')}>
                    Mark all read
                </button>
            )}
            <ul>
                {notifications.map((n) => (
                    <li key={n.id}>
                        <strong>{n.title}</strong> — {n.body}{' '}
                        {n.link && <Link href={n.link}>View</Link>}{' '}
                        {!n.read_at && (
                            <button type="button" disabled={read.processing} onClick={() => read.post(`/notifications/${n.id}/read`)}>
                                Mark read
                            </button>
                        )}
                    </li>
                ))}
            </ul>
        </main>
    );
}
