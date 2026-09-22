import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';
import echo from '../../echo';

export default function Queue() {
    const { practitioner_id, departments, department_id, schedule, snapshot: initial, status } = usePage().props;
    const [snapshot, setSnapshot] = useState(initial);
    const [loadingActionId, setLoadingActionId] = useState(null);

    useEffect(() => {
        setSnapshot(initial);
    }, [initial]);

    useEffect(() => {
        if (!department_id) return undefined;
        const channel = echo.channel(`queue.${department_id}`).listen('QueueUpdated', (e) => setSnapshot(e));
        return () => {
            echo.leaveChannel(`queue.${department_id}`);
        };
    }, [department_id]);

    const handleDepartmentChange = (e) => {
        const deptId = e.target.value;
        router.get('/staff/my-queue', { department_id: deptId }, { preserveState: true, preserveScroll: true });
    };

    const performOptimisticAction = (actionId, url, optimisticMutate) => {
        setLoadingActionId(actionId);
        const previousSnapshot = { ...snapshot };

        // Optimistically update local React UI state instantly (0ms latency)
        if (optimisticMutate) {
            setSnapshot(optimisticMutate);
        }

        router.post(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setLoadingActionId(null),
                onError: () => {
                    // Revert state if backend request fails
                    setSnapshot(previousSnapshot);
                },
            }
        );
    };

    const handleCallNext = () => {
        const url = `/staff/queue/call-next?department_id=${department_id}&practitioner_id=${practitioner_id}`;
        const myWaiting = mine(snapshot?.waiting);
        const nextPatient = myWaiting[0];

        performOptimisticAction('call-next', url, (prev) => {
            if (!nextPatient || !prev) return prev;
            return {
                ...prev,
                serving: nextPatient,
                waiting: prev.waiting.filter((item) => item.id !== nextPatient.id),
            };
        });
    };

    const handleCallSpecific = (entry) => {
        const url = `/staff/queue/${entry.id}/call`;
        performOptimisticAction(`call-${entry.id}`, url, (prev) => {
            if (!prev) return prev;
            return {
                ...prev,
                serving: entry,
                waiting: prev.waiting.filter((item) => item.id !== entry.id),
            };
        });
    };

    const handleComplete = (entry) => {
        const url = `/staff/queue/${entry.id}/complete`;
        performOptimisticAction(`complete-${entry.id}`, url, (prev) => {
            if (!prev) return prev;
            return {
                ...prev,
                serving: prev.serving?.id === entry.id ? null : prev.serving,
                waiting: prev.waiting.filter((item) => item.id !== entry.id),
            };
        });
    };

    const handleRecall = (entry) => {
        const url = `/staff/queue/${entry.id}/recall`;
        performOptimisticAction(`recall-${entry.id}`, url, (prev) => {
            if (!prev) return prev;
            return {
                ...prev,
                skipped: prev.skipped.filter((item) => item.id !== entry.id),
                waiting: [entry, ...prev.waiting],
            };
        });
    };

    const mine = (list) => (list ?? []).filter((e) => e.practitioner?.id === practitioner_id);

    return (
        <main className="p-8 font-sans max-w-4xl mx-auto">
            <h1 className="text-3xl font-bold text-gray-900 mb-4">My Queue</h1>
            {status && <p className="mb-4 p-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md font-medium text-sm">{status}</p>}

            <div className="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Department
                    <select
                        name="department_id"
                        value={department_id ?? ''}
                        onChange={handleDepartmentChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border sm:text-sm"
                    >
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </label>
            </div>

            <div className="mb-8">
                <h2 className="text-lg font-semibold text-gray-800 mb-2">Today&apos;s Schedule</h2>
                <div className="flex flex-wrap gap-2">
                    {(schedule ?? []).map((s) => (
                        <span key={s.id} className="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium border border-blue-100">
                            {s.start_time.slice(0, 5)}–{s.end_time.slice(0, 5)} · {s.slot_duration_minutes}min
                        </span>
                    ))}
                </div>
            </div>

            {snapshot && (
                <div className="space-y-8">
                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <span className="text-xs uppercase tracking-wider text-gray-500 font-semibold block mb-1">Currently Serving</span>
                            <h2 className="text-4xl font-extrabold text-blue-600">{snapshot.serving?.queue_number ?? '—'}</h2>
                            {snapshot.serving?.patient && (
                                <p className="text-sm font-medium text-gray-700 mt-1">{snapshot.serving.patient.full_name}</p>
                            )}
                        </div>
                        <AsyncButton
                            onClick={handleCallNext}
                            loading={loadingActionId === 'call-next'}
                            loadingText="Calling next..."
                            variant="primary"
                            className="px-6 py-3 text-base font-semibold shadow-md"
                        >
                            Call Next (Mine)
                        </AsyncButton>
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-3">My Waiting Patients</h3>
                        {mine(snapshot.waiting).length === 0 ? (
                            <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-dashed border-gray-200">No waiting patients in queue.</p>
                        ) : (
                            <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                {mine(snapshot.waiting).map((e) => (
                                    <li key={e.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <div>
                                            <span className="font-bold text-gray-900 text-lg mr-2">#{e.queue_number}</span>
                                            <span className="font-medium text-gray-700">{e.patient?.full_name}</span>
                                            {e.is_recalled && (
                                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                    Recalled
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex space-x-2">
                                            <AsyncButton
                                                onClick={() => handleCallSpecific(e)}
                                                loading={loadingActionId === `call-${e.id}`}
                                                loadingText="Calling..."
                                                variant="outline"
                                                className="text-xs py-1.5 px-3"
                                            >
                                                Call
                                            </AsyncButton>
                                            <AsyncButton
                                                onClick={() => handleComplete(e)}
                                                loading={loadingActionId === `complete-${e.id}`}
                                                loadingText="Completing..."
                                                variant="success"
                                                className="text-xs py-1.5 px-3"
                                            >
                                                Complete
                                            </AsyncButton>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-3">My Skipped Patients</h3>
                        {mine(snapshot.skipped).length === 0 ? (
                            <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-dashed border-gray-200">No skipped patients.</p>
                        ) : (
                            <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                {mine(snapshot.skipped).map((e) => (
                                    <li key={e.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <div>
                                            <span className="font-bold text-gray-900 text-lg mr-2">#{e.queue_number}</span>
                                            <span className="font-medium text-gray-700">{e.patient?.full_name}</span>
                                        </div>
                                        <AsyncButton
                                            onClick={() => handleRecall(e)}
                                            loading={loadingActionId === `recall-${e.id}`}
                                            loadingText="Recalling..."
                                            variant="secondary"
                                            className="text-xs py-1.5 px-3"
                                        >
                                            Recall
                                        </AsyncButton>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            )}
        </main>
    );
}
