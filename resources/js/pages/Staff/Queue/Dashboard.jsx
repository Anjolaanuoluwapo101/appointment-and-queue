import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AsyncButton from '../../../Components/AsyncButton';
import echo from '../../../echo';

function EntryActions({ entry, onAction, loadingId }) {
    const isWorking = (action) => loadingId === `${action}-${entry.id}`;

    return (
        <span className="inline-flex space-x-1.5 ml-3">
            {(entry.status === 'waiting' || entry.status === 'skipped') && (
                <AsyncButton
                    onClick={() => onAction(`call-${entry.id}`, `/staff/queue/${entry.id}/call`)}
                    loading={isWorking('call')}
                    loadingText="Calling..."
                    variant="outline"
                    className="text-xs py-1 px-2.5"
                >
                    Call
                </AsyncButton>
            )}
            {entry.status === 'called' && (
                <>
                    <AsyncButton
                        onClick={() => onAction(`begin-${entry.id}`, `/staff/queue/${entry.id}/begin`)}
                        loading={isWorking('begin')}
                        loadingText="Starting..."
                        variant="primary"
                        className="text-xs py-1 px-2.5"
                    >
                        Begin
                    </AsyncButton>
                    <AsyncButton
                        onClick={() => onAction(`skip-${entry.id}`, `/staff/queue/${entry.id}/skip`)}
                        loading={isWorking('skip')}
                        loadingText="Skipping..."
                        variant="secondary"
                        className="text-xs py-1 px-2.5"
                    >
                        Skip
                    </AsyncButton>
                    <AsyncButton
                        onClick={() => onAction(`complete-${entry.id}`, `/staff/queue/${entry.id}/complete`)}
                        loading={isWorking('complete')}
                        loadingText="Completing..."
                        variant="success"
                        className="text-xs py-1 px-2.5"
                    >
                        Complete
                    </AsyncButton>
                </>
            )}
            {entry.status === 'in_consultation' && (
                <AsyncButton
                    onClick={() => onAction(`complete-${entry.id}`, `/staff/queue/${entry.id}/complete`)}
                    loading={isWorking('complete')}
                    loadingText="Completing..."
                    variant="success"
                    className="text-xs py-1 px-2.5"
                >
                    Complete
                </AsyncButton>
            )}
            {entry.status === 'skipped' && (
                <AsyncButton
                    onClick={() => onAction(`recall-${entry.id}`, `/staff/queue/${entry.id}/recall`)}
                    loading={isWorking('recall')}
                    loadingText="Recalling..."
                    variant="secondary"
                    className="text-xs py-1 px-2.5"
                >
                    Recall
                </AsyncButton>
            )}
            {['waiting', 'called', 'skipped'].includes(entry.status) && (
                <AsyncButton
                    onClick={() => onAction(`cancel-${entry.id}`, `/staff/queue/${entry.id}/cancel`)}
                    loading={isWorking('cancel')}
                    loadingText="Cancelling..."
                    variant="danger"
                    className="text-xs py-1 px-2.5"
                >
                    Cancel
                </AsyncButton>
            )}
        </span>
    );
}

export default function Dashboard() {
    const { departments, department_id, snapshot: initial, status } = usePage().props;
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
        router.get('/staff/queue', { department_id: deptId }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (actionId, url) => {
        setLoadingActionId(actionId);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setLoadingActionId(null),
            }
        );
    };

    return (
        <main className="p-8 font-sans max-w-5xl mx-auto">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-3xl font-bold text-gray-900">Reception Queue Dashboard</h1>
                <Link
                    href="/staff/walk-in"
                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-all shadow-sm active:scale-[0.98]"
                >
                    + Walk-in quick-add
                </Link>
            </div>

            {status && (
                <p className="mb-6 p-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md font-medium text-sm">
                    {status}
                </p>
            )}

            <div className="mb-8 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Department Filter
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

            {snapshot && (
                <div className="space-y-8">
                    <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div>
                            <span className="text-xs uppercase tracking-wider text-gray-500 font-semibold block mb-1">
                                Currently Serving ({snapshot.department.name})
                            </span>
                            <h2 className="text-4xl font-extrabold text-blue-600">{snapshot.serving?.queue_number ?? '—'}</h2>
                            {snapshot.serving?.patient && (
                                <p className="text-sm font-medium text-gray-700 mt-1">{snapshot.serving.patient.full_name}</p>
                            )}
                        </div>
                        <AsyncButton
                            onClick={() => handleAction('call-next', `/staff/queue/call-next?department_id=${department_id}`)}
                            loading={loadingActionId === 'call-next'}
                            loadingText="Calling next..."
                            variant="primary"
                            className="px-6 py-3 text-base font-semibold shadow-md"
                        >
                            Call Next Patient
                        </AsyncButton>
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-3">Waiting Patients</h3>
                        {snapshot.waiting.length === 0 ? (
                            <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-dashed border-gray-200">
                                No waiting patients.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                {snapshot.waiting.map((e) => (
                                    <li key={e.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <div>
                                            <span className="font-bold text-gray-900 text-lg mr-2">#{e.queue_number}</span>
                                            <span className="font-medium text-gray-800">{e.patient?.full_name}</span>
                                            {e.is_recalled && (
                                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                    Recalled
                                                </span>
                                            )}
                                            <span className="text-xs text-gray-500 block">Practitioner: {e.practitioner?.full_name ?? 'Unassigned'}</span>
                                        </div>
                                        <EntryActions entry={e} onAction={handleAction} loadingId={loadingActionId} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-3">Skipped Patients</h3>
                        {snapshot.skipped.length === 0 ? (
                            <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-dashed border-gray-200">
                                No skipped patients.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                {snapshot.skipped.map((e) => (
                                    <li key={e.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                        <div>
                                            <span className="font-bold text-gray-900 text-lg mr-2">#{e.queue_number}</span>
                                            <span className="font-medium text-gray-800">{e.patient?.full_name}</span>
                                        </div>
                                        <EntryActions entry={e} onAction={handleAction} loadingId={loadingActionId} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-3">Cancelled Patients</h3>
                        {(snapshot.cancelled ?? []).length === 0 ? (
                            <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-dashed border-gray-200">
                                No cancelled entries today.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                {(snapshot.cancelled ?? []).map((e) => (
                                    <li key={e.id} className="p-4 flex items-center justify-between bg-gray-50/50">
                                        <div>
                                            <span className="font-bold text-gray-400 text-lg mr-2">#{e.queue_number}</span>
                                            <span className="font-medium text-gray-500 line-through">{e.patient?.full_name}</span>
                                        </div>
                                        <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                            Cancelled
                                        </span>
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
