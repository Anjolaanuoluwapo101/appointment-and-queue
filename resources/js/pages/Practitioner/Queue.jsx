import React, { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import { Badge } from '../../Components/Badge';
import { Card } from '../../Components/Card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../Components/Table';
import AppLayout from '../../Layouts/AppLayout';
import echo from '../../echo';

export default function Queue() {
    const { practitioner_id, departments, department_id, schedule, snapshot: initial } = usePage().props;
    const [snapshot, setSnapshot] = useState(initial);
    const [loadingActionId, setLoadingActionId] = useState(null);

    useEffect(() => {
        setSnapshot(initial);
    }, [initial]);

    useEffect(() => {
        if (!department_id) return undefined;
        // Broadcasts carry numbers only (public channel); refetch the
        // full authed snapshot instead of trusting the payload.
        const channel = echo.channel(`queue.${department_id}`).listen('QueueUpdated', () => {
            router.reload({ only: ['snapshot'] });
        });
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
                    setSnapshot(previousSnapshot);
                },
            }
        );
    };

    const mine = (list) => (list ?? []).filter((e) => e.practitioner?.id === practitioner_id);

    const myWaiting = mine(snapshot?.waiting);
    const nextPatient = myWaiting[0];
    const hasActiveServing = !!snapshot?.serving;

    const handleCompleteAndCallNext = () => {
        const url = `/staff/queue/complete-and-call-next?department_id=${department_id}&practitioner_id=${practitioner_id}`;
        performOptimisticAction('complete-and-call-next', url, (prev) => {
            if (!prev) return prev;
            return {
                ...prev,
                serving: nextPatient ?? null,
                waiting: prev.waiting.filter((item) => item.id !== nextPatient?.id),
            };
        });
    };

    const handleCallNext = () => {
        const url = `/staff/queue/call-next?department_id=${department_id}&practitioner_id=${practitioner_id}`;
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

    const handleSkip = (entry) => {
        const url = `/staff/queue/${entry.id}/skip`;
        performOptimisticAction(`skip-${entry.id}`, url, (prev) => {
            if (!prev) return prev;
            return {
                ...prev,
                waiting: prev.waiting.filter((item) => item.id !== entry.id),
                skipped: [{ ...entry, status: 'skipped' }, ...(prev.skipped ?? [])],
            };
        });
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-7xl mx-auto bg-white py-6">
                {/* Header Strip & Department Control */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-zinc-900">Practitioner Consultation Queue</h1>
                        <p className="text-xs text-zinc-500">Live consultation management for active shift</p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <select
                            name="department_id"
                            value={department_id ?? ''}
                            onChange={handleDepartmentChange}
                            className="bg-white border border-zinc-200 rounded px-3 py-1.5 text-xs text-zinc-700 font-medium focus:outline-none focus:border-zinc-400 shadow-sm"
                        >
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>

                        {hasActiveServing ? (
                            <AsyncButton
                                onClick={handleCompleteAndCallNext}
                                loading={loadingActionId === 'complete-and-call-next'}
                                loadingText="Completing & calling..."
                                variant="primary"
                                className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-1.5 rounded text-xs shadow-sm"
                            >
                                Complete & Call Next {nextPatient ? `(${nextPatient.queue_number})` : ''}
                            </AsyncButton>
                        ) : (
                            <AsyncButton
                                onClick={handleCallNext}
                                loading={loadingActionId === 'call-next'}
                                loadingText="Calling next..."
                                disabled={myWaiting.length === 0}
                                variant="primary"
                                className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-1.5 rounded text-xs shadow-sm disabled:bg-zinc-100 disabled:text-zinc-400"
                            >
                                {myWaiting.length > 0 ? `Call Next Patient (${nextPatient?.queue_number})` : 'Queue Empty (0 Waiting)'}
                            </AsyncButton>
                        )}
                    </div>
                </div>

                {/* Shift Schedule Strip */}
                <div className="flex items-center space-x-2 text-xs">
                    <span className="font-semibold text-zinc-500">Today&apos;s Shift:</span>
                    {(schedule ?? []).map((s) => (
                        <Badge key={s.id} variant="outline" className="font-mono text-[11px]">
                            {s.start_time.slice(0, 5)}–{s.end_time.slice(0, 5)} ({s.slot_duration_minutes} min slots)
                        </Badge>
                    ))}
                </div>

                {snapshot && (
                    <div className="space-y-6">
                        {/* Currently Serving Active Card */}
                        <Card className="bg-white border border-zinc-200 rounded-lg p-5 shadow-sm">
                            <div className="flex items-center justify-between border-b border-zinc-100 pb-3 mb-4">
                                <span className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Patient In Room</span>
                                <Badge variant="success">In Room</Badge>
                            </div>

                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div className="flex items-center space-x-3">
                                        <span className="text-3xl font-bold font-mono text-zinc-900">
                                            {snapshot.serving?.queue_number ?? '—'}
                                        </span>
                                        {snapshot.serving?.patient && (
                                            <h2 className="text-xl font-bold text-zinc-900">{snapshot.serving.patient.full_name}</h2>
                                        )}
                                    </div>
                                    {snapshot.serving?.patient && (
                                        <p className="text-xs text-zinc-500 mt-1">
                                            Hospital No: <strong className="font-mono text-zinc-700">{snapshot.serving.patient.patient_number || `PAT-#${snapshot.serving.patient.id}`}</strong> · Scheduled Consultation
                                        </p>
                                    )}
                                </div>

                                {snapshot.serving && snapshot.serving?.practitioner?.id === practitioner_id ? (
                                    <div className="flex items-center space-x-2">
                                        <AsyncButton
                                            onClick={handleCompleteAndCallNext}
                                            loading={loadingActionId === 'complete-and-call-next'}
                                            loadingText="Completing & calling..."
                                            variant="primary"
                                            className="bg-zinc-900 text-white hover:bg-zinc-800 text-xs font-semibold px-4 py-2 rounded shadow-sm"
                                        >
                                            Complete & Call Next
                                        </AsyncButton>
                                    </div>
                                ) : (
                                    snapshot.serving && (
                                        <p className="text-xs text-zinc-500 italic">
                                            With {snapshot.serving?.practitioner?.full_name ?? 'another practitioner'} — actions available on their board.
                                        </p>
                                    )
                                )}
                            </div>
                        </Card>

                        {/* Waiting Patients Table */}
                        <div className="space-y-3">
                            <h3 className="text-sm font-bold text-zinc-900">My Waiting Patients ({myWaiting.length})</h3>

                            {myWaiting.length === 0 ? (
                                <div className="bg-white border border-zinc-200 rounded-lg p-6 text-center text-xs text-zinc-500">
                                    No waiting patients in queue.
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-24">Ticket</TableHead>
                                            <TableHead>Hospital No.</TableHead>
                                            <TableHead>Patient Name</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {myWaiting.map((e) => (
                                            <TableRow key={e.id}>
                                                <TableCell className="font-bold font-mono text-zinc-900">{e.queue_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-zinc-600">{e.patient?.patient_number || `PAT-#${e.patient_id}`}</TableCell>
                                                <TableCell className="font-medium text-zinc-900">
                                                    {e.patient?.full_name}
                                                    {e.is_recalled && <Badge variant="amber" className="ml-2">Recalled</Badge>}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">Waiting</Badge>
                                                </TableCell>
                                                <TableCell className="text-right space-x-1">
                                                    <AsyncButton
                                                        onClick={() => handleCallSpecific(e)}
                                                        loading={loadingActionId === `call-${e.id}`}
                                                        loadingText="Calling..."
                                                        variant="primary"
                                                        className="bg-zinc-900 text-white text-[11px] px-2.5 py-1 rounded"
                                                    >
                                                        Call
                                                    </AsyncButton>
                                                    <AsyncButton
                                                        onClick={() => handleSkip(e)}
                                                        loading={loadingActionId === `skip-${e.id}`}
                                                        loadingText="Skipping..."
                                                        variant="outline"
                                                        className="text-[11px] px-2.5 py-1 rounded"
                                                    >
                                                        Skip
                                                    </AsyncButton>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </div>

                        {/* Skipped Patients Table */}
                        {mine(snapshot.skipped).length > 0 && (
                            <div className="space-y-3">
                                <h3 className="text-sm font-bold text-zinc-900">Skipped Patients ({mine(snapshot.skipped).length})</h3>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-24">Ticket</TableHead>
                                            <TableHead>Hospital No.</TableHead>
                                            <TableHead>Patient Name</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {mine(snapshot.skipped).map((e) => (
                                            <TableRow key={e.id}>
                                                <TableCell className="font-bold font-mono text-zinc-900">{e.queue_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-zinc-600">{e.patient?.patient_number || `PAT-#${e.patient_id}`}</TableCell>
                                                <TableCell className="font-medium text-zinc-900">{e.patient?.full_name}</TableCell>
                                                <TableCell className="text-right">
                                                    <AsyncButton
                                                        onClick={() => handleRecall(e)}
                                                        loading={loadingActionId === `recall-${e.id}`}
                                                        loadingText="Recalling..."
                                                        variant="outline"
                                                        className="text-[11px] px-2.5 py-1 rounded"
                                                    >
                                                        Recall
                                                    </AsyncButton>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
