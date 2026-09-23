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

    const nextQueue = snapshot.waiting.slice(0, 4);

    return (
        <div className="min-h-screen bg-white text-zinc-900 flex flex-col justify-between p-8 md:p-16 font-sans antialiased">
            {/* Header Banner */}
            <div className="border-b-2 border-zinc-900 pb-6 flex items-center justify-between">
                <div>
                    <span className="text-sm font-mono uppercase tracking-widest text-zinc-500 font-bold block">Hospital Public Queue Display</span>
                    <h1 className="text-3xl md:text-5xl font-extrabold text-zinc-900 uppercase tracking-tight">{snapshot.department.name}</h1>
                </div>
                <div className="flex items-center space-x-2 bg-zinc-100 border border-zinc-200 px-4 py-2 rounded-full">
                    <span className="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span className="text-xs font-semibold text-zinc-800 uppercase font-mono">Live Sync Active</span>
                </div>
            </div>

            {/* Giant Active Ticket Callout Box */}
            <div className="my-auto py-12 text-center space-y-4">
                <span className="text-sm md:text-base font-extrabold uppercase tracking-widest text-zinc-500 block">Now Calling to Consultation</span>
                <div className="text-7xl md:text-9xl font-black font-mono tracking-tight text-zinc-900">
                    {snapshot.serving?.queue_number ?? '—'}
                </div>
            </div>

            {/* Next-in-Line Ticker Bar */}
            <div className="border-t-2 border-zinc-900 pt-6">
                <h3 className="text-xs font-mono uppercase tracking-widest text-zinc-400 font-semibold mb-4 text-center sm:text-left">
                    Next Patients In Line
                </h3>
                {nextQueue.length === 0 ? (
                    <p className="text-sm font-medium text-zinc-400 italic text-center sm:text-left">No waiting tickets in line.</p>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        {nextQueue.map((item, idx) => (
                            <div key={item.id} className="border border-zinc-200 rounded-lg p-4 text-center bg-white shadow-sm">
                                <span className="text-[10px] uppercase font-mono text-zinc-400 block font-semibold">Sequence #{idx + 1}</span>
                                <span className="text-2xl sm:text-3xl font-extrabold font-mono text-zinc-900 block mt-1">{item.queue_number}</span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
