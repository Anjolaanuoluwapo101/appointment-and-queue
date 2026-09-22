import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import echo from '../../echo';
export default function Board() {
    const { snapshot: initial } = usePage().props;
    const [snapshot, setSnapshot] = useState(initial);

    useEffect(() => {
        setSnapshot(initial);
    }, [initial]);

    useEffect(() => {
        const channel = echo.channel(`queue.${initial.department.id}`).listen('QueueUpdated', (e) => setSnapshot(e));
        return () => {
            echo.leaveChannel(`queue.${initial.department.id}`);
        };
    }, [initial.department.id]);

    const next = snapshot.waiting.slice(0, 3).map((e) => e.queue_number);

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', textAlign: 'center' }}>
            <h1>{snapshot.department.name.toUpperCase()}</h1>
            <h2>Currently Serving: {snapshot.serving?.queue_number ?? '—'}</h2>
            <p>Next: {next.length > 0 ? next.join(', ') : '—'}</p>
        </main>
    );
}
