import React, { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import AsyncButton from '../../../Components/AsyncButton';
import { Badge } from '../../../Components/Badge';
import echo from '../../../echo';

function getWaitTime(createdAt) {
    if (!createdAt) return null;
    const created = new Date(createdAt);
    const now = new Date();
    const diffMins = Math.max(0, Math.floor((now - created) / (1000 * 60)));
    if (diffMins < 60) return `${diffMins}m`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    return `${hours}h ${mins}m`;
}

function EntryActions({ entry, onAction, loadingId }) {
    const isWorking = (action) => loadingId === `${action}-${entry.id}`;

    // Mirrors the backend state guards in QueueService: only show actions
    // the entry's status actually allows, so no button can 422.
    const allowed = {
        waiting: ['call', 'skip', 'cancel'],
        called: ['begin', 'skip', 'done', 'cancel'],
        in_consultation: ['done'],
        skipped: ['call', 'recall', 'cancel'],
    }[entry.status] ?? [];

    const buttons = {
        call: (
            <AsyncButton
                onClick={() => onAction(`call-${entry.id}`, `/staff/queue/${entry.id}/call`)}
                loading={isWorking('call')}
                loadingText="Calling..."
                variant="secondary"
                className="px-3 py-1 text-xs font-semibold bg-zinc-900 text-white hover:bg-zinc-800 rounded transition-colors shadow-sm"
            >
                Call
            </AsyncButton>
        ),
        skip: (
            <AsyncButton
                onClick={() => onAction(`skip-${entry.id}`, `/staff/queue/${entry.id}/skip`)}
                loading={isWorking('skip')}
                loadingText="Skipping..."
                variant="outline"
                className="px-2.5 py-1 text-xs font-semibold border-zinc-200 text-zinc-700 hover:bg-zinc-50 rounded"
            >
                Skip
            </AsyncButton>
        ),
        begin: (
            <AsyncButton
                onClick={() => onAction(`begin-${entry.id}`, `/staff/queue/${entry.id}/begin`)}
                loading={isWorking('begin')}
                loadingText="Starting..."
                variant="outline"
                className="px-2.5 py-1 text-xs font-semibold border-emerald-200 text-emerald-800 hover:bg-emerald-50 rounded"
            >
                Begin
            </AsyncButton>
        ),
        done: (
            <AsyncButton
                onClick={() => onAction(`complete-${entry.id}`, `/staff/queue/${entry.id}/complete`)}
                loading={isWorking('complete')}
                loadingText="Done..."
                variant="outline"
                className="px-2.5 py-1 text-xs font-semibold border-zinc-200 text-zinc-600 hover:bg-zinc-100 rounded"
            >
                Done
            </AsyncButton>
        ),
        recall: (
            <AsyncButton
                onClick={() => onAction(`recall-${entry.id}`, `/staff/queue/${entry.id}/recall`)}
                loading={isWorking('recall')}
                loadingText="Recalling..."
                variant="outline"
                className="px-2.5 py-1 text-xs font-semibold border-amber-200 text-amber-800 hover:bg-amber-50 rounded"
            >
                Recall
            </AsyncButton>
        ),
        cancel: (
            <AsyncButton
                onClick={() => onAction(`cancel-${entry.id}`, `/staff/queue/${entry.id}/cancel`)}
                loading={isWorking('cancel')}
                loadingText="Cancelling..."
                variant="outline"
                className="px-2.5 py-1 text-xs font-semibold border-rose-200 text-rose-700 hover:bg-rose-50 rounded"
            >
                Cancel
            </AsyncButton>
        ),
    };

    return (
        <span className="inline-flex flex-wrap gap-1.5 ml-3">
            {allowed.map((action) => (
                <span key={action}>{buttons[action]}</span>
            ))}
        </span>
    );
}

export default function Dashboard() {
    const { departments, department_id, overview, snapshot: initialSnapshot, today_arrivals = [], status } = usePage().props;
    const [snapshot, setSnapshot] = useState(initialSnapshot);
    const [loadingActionId, setLoadingActionId] = useState(null);

    useEffect(() => {
        setSnapshot(initialSnapshot);
    }, [initialSnapshot]);

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

    const handleSelectDepartment = (deptId) => {
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

    const handleCheckInArrival = (appointmentId) => {
        setLoadingActionId(`checkin-${appointmentId}`);
        router.post(
            `/staff/appointments/${appointmentId}/check-in`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setLoadingActionId(null),
            }
        );
    };

    const hasActiveServing = !!snapshot?.serving;
    const nextWaitingPatient = snapshot?.waiting?.[0];
    const waitingCount = snapshot?.waiting?.length ?? 0;

    const totalWaitingAcrossDepts = overview?.reduce((acc, d) => acc + (d.waiting_count || 0), 0) ?? 0;
    const totalServingAcrossDepts = overview?.filter((d) => d.serving_number).length ?? 0;

    return (
        <AppLayout activeRoute="staff.queue">
            <div className="space-y-6 max-w-7xl mx-auto bg-white py-4 px-2 sm:px-4">
                {/* Header Command Bar */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 pb-4">
                    <div>
                        <div className="flex items-center space-x-2">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Reception Desk Command Center</h1>
                            <Badge variant="outline" className="font-mono text-xs">Live Desk</Badge>
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">
                            Real-time department queue control, quick desk check-in, and instant patient file access.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href="/staff/search"
                            className="bg-white hover:bg-zinc-50 text-zinc-700 font-medium px-3 py-2 rounded text-xs border border-zinc-200 transition-colors shadow-sm"
                        >
                            Search Patient
                        </Link>
                        <Link
                            href="/staff/patients"
                            className="bg-white hover:bg-zinc-50 text-zinc-700 font-medium px-3 py-2 rounded text-xs border border-zinc-200 transition-colors shadow-sm"
                        >
                            Patient Directory
                        </Link>
                        <Link
                            href="/staff/walk-in"
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Walk-in Ticket
                        </Link>
                    </div>
                </div>

                {status && (
                    <div className="p-3 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded text-xs font-medium">
                        {status}
                    </div>
                )}

                {/* Live System Metrics Ribbon */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Card className="bg-white border border-zinc-200">
                        <CardContent className="p-3">
                            <span className="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider block">Total Waiting</span>
                            <span className="text-2xl font-bold font-mono text-zinc-900">{totalWaitingAcrossDepts}</span>
                        </CardContent>
                    </Card>
                    <Card className="bg-white border border-zinc-200">
                        <CardContent className="p-3">
                            <span className="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider block">In Consultation</span>
                            <span className="text-2xl font-bold font-mono text-emerald-700">{totalServingAcrossDepts}</span>
                        </CardContent>
                    </Card>
                    <Card className="bg-white border border-zinc-200">
                        <CardContent className="p-3">
                            <span className="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider block">Completed Today</span>
                            <span className="text-2xl font-bold font-mono text-zinc-900">{snapshot?.completed_count || 0}</span>
                        </CardContent>
                    </Card>
                    <Card className="bg-white border border-zinc-200">
                        <CardContent className="p-3">
                            <span className="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider block">Expected Desk Arrivals</span>
                            <span className="text-2xl font-bold font-mono text-amber-700">{today_arrivals.length}</span>
                        </CardContent>
                    </Card>
                </div>

                {/* All-Departments Live Overview Grid */}
                <section className="space-y-2">
                    <h2 className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">All-Departments Live Status</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {overview?.map((dept) => {
                            const isSelected = Number(department_id) === Number(dept.id);
                            return (
                                <button
                                    key={dept.id}
                                    type="button"
                                    onClick={() => handleSelectDepartment(dept.id)}
                                    className={`text-left p-3.5 rounded-lg border transition-all ${isSelected
                                            ? 'bg-zinc-900 text-white border-zinc-900 shadow-md ring-2 ring-zinc-900 ring-offset-1'
                                            : 'bg-white text-zinc-900 border-zinc-200 hover:border-zinc-300 shadow-sm'
                                        }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className={`font-semibold text-xs ${isSelected ? 'text-white' : 'text-zinc-900'}`}>{dept.name}</span>
                                        <Badge variant={isSelected ? 'outline' : 'secondary'} className="text-[10px]">
                                            {dept.waiting_count} Waiting
                                        </Badge>
                                    </div>
                                    <div className="mt-2 text-xs flex items-baseline justify-between">
                                        <span className={isSelected ? 'text-zinc-300' : 'text-zinc-500'}>
                                            {dept.room_label || 'Desk'}
                                        </span>
                                        <span className={`font-mono font-bold ${isSelected ? 'text-emerald-400' : 'text-zinc-900'}`}>
                                            {dept.serving_number ? `Serving: ${dept.serving_number}` : 'Idle'}
                                        </span>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </section>

                {/* Department Selection Tabs */}
                <div className="border-b border-zinc-200 flex space-x-2 overflow-x-auto pb-1">
                    {departments?.map((d) => {
                        const isActive = Number(department_id) === Number(d.id);
                        return (
                            <button
                                key={d.id}
                                type="button"
                                onClick={() => handleSelectDepartment(d.id)}
                                className={`px-4 py-2 text-xs font-semibold rounded-t-md transition-colors whitespace-nowrap ${isActive
                                        ? 'bg-zinc-900 text-white border-b-2 border-zinc-900'
                                        : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50'
                                    }`}
                            >
                                {d.name}
                            </button>
                        );
                    })}
                </div>

                {/* Main 2-Column Command Workspace */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column (70% width): Live Active Queue */}
                    <div className="lg:col-span-2 space-y-6">
                        {snapshot && (
                            <>
                                {/* Active Room Consultation Card */}
                                <Card className="bg-white border border-zinc-200 shadow-sm">
                                    <CardContent className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4">
                                        <div>
                                            <span className="text-[10px] font-mono uppercase tracking-wider text-zinc-400 block mb-1">
                                                Active Room Consultation ({snapshot.department.name})
                                            </span>
                                            <div className="flex items-baseline space-x-3">
                                                <h2 className="text-4xl font-extrabold tracking-tight text-zinc-900 font-mono">
                                                    {snapshot.serving?.queue_number ?? '—'}
                                                </h2>
                                                {snapshot.serving?.patient && (
                                                    <div className="flex items-center space-x-2">
                                                        <Link
                                                            href={`/staff/patients/${snapshot.serving.patient.id}`}
                                                            className="text-xs font-bold text-zinc-900 hover:text-blue-600 hover:underline"
                                                        >
                                                            {snapshot.serving.patient.full_name}
                                                        </Link>
                                                        {snapshot.serving.patient.patient_number && (
                                                            <Badge variant="outline" className="font-mono text-[10px]">
                                                                {snapshot.serving.patient.patient_number}
                                                            </Badge>
                                                        )}
                                                        <Badge variant="success" className="text-[10px]">In Consultation</Badge>
                                                        {snapshot.serving.appointment?.is_walk_in ? (
                                                            <Badge variant="outline" className="text-[10px] bg-emerald-50 text-emerald-800 border-emerald-200">
                                                                Walk-In
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="text-[10px]">Scheduled</Badge>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            {hasActiveServing ? (
                                                <AsyncButton
                                                    onClick={() => handleAction('complete-and-call-next', `/staff/queue/complete-and-call-next?department_id=${department_id}`)}
                                                    loading={loadingActionId === 'complete-and-call-next'}
                                                    loadingText="Completing & calling..."
                                                    variant="primary"
                                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-5 py-2.5 rounded text-xs shadow-sm"
                                                >
                                                    Complete & Call Next {nextWaitingPatient ? `(${nextWaitingPatient.queue_number})` : ''}
                                                </AsyncButton>
                                            ) : (
                                                <AsyncButton
                                                    onClick={() => handleAction('call-next', `/staff/queue/call-next?department_id=${department_id}`)}
                                                    loading={loadingActionId === 'call-next'}
                                                    loadingText="Calling next..."
                                                    disabled={waitingCount === 0}
                                                    variant="primary"
                                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-5 py-2.5 rounded text-xs shadow-sm disabled:bg-zinc-100 disabled:text-zinc-400"
                                                >
                                                    {waitingCount > 0 ? `Call Next Patient (${nextWaitingPatient?.queue_number})` : 'Queue Empty (0 Waiting)'}
                                                </AsyncButton>
                                            )}
                                            {snapshot.serving && (
                                                <EntryActions entry={snapshot.serving} onAction={handleAction} loadingId={loadingActionId} />
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Waiting Queue Table */}
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-sm font-semibold tracking-tight text-zinc-900">
                                            Waiting Queue ({snapshot.waiting.length})
                                        </h3>
                                        <span className="text-xs text-zinc-400 font-mono">Auto-refreshes live</span>
                                    </div>

                                    {snapshot.waiting.length === 0 ? (
                                        <div className="p-6 rounded-md border border-zinc-200 bg-white text-center text-xs text-zinc-500 italic">
                                            No patients currently waiting in this queue.
                                        </div>
                                    ) : (
                                        <div className="divide-y divide-zinc-100 bg-white rounded-md border border-zinc-200 overflow-hidden shadow-sm">
                                            {snapshot.waiting.map((e) => {
                                                const waitTimeStr = getWaitTime(e.created_at);
                                                const isWalkIn = e.appointment?.is_walk_in;
                                                const payStatus = e.appointment?.payment_status;

                                                return (
                                                    <div key={e.id} className="p-3 hover:bg-zinc-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                        <div className="flex items-center space-x-3">
                                                            <span className="font-mono font-bold text-base text-zinc-900">{e.queue_number}</span>
                                                            <div className="space-y-0.5">
                                                                <div className="flex items-center space-x-2">
                                                                    <Link
                                                                        href={`/staff/patients/${e.patient?.id}`}
                                                                        className="font-semibold text-xs text-zinc-900 hover:text-blue-600 hover:underline"
                                                                    >
                                                                        {e.patient?.full_name}
                                                                    </Link>
                                                                    <span className="text-xs font-mono text-zinc-400">
                                                                        ({e.patient?.patient_number || `PAT-#${e.patient_id}`})
                                                                    </span>
                                                                </div>

                                                                {/* Badges strip */}
                                                                <div className="flex items-center space-x-1.5 pt-0.5">
                                                                    {isWalkIn ? (
                                                                        <Badge variant="outline" className="text-[9px] bg-emerald-50 text-emerald-800 border-emerald-200">
                                                                            Walk-In
                                                                        </Badge>
                                                                    ) : (
                                                                        <Badge variant="outline" className="text-[9px]">
                                                                            Scheduled
                                                                        </Badge>
                                                                    )}

                                                                    {payStatus === 'paid' ? (
                                                                        <Badge variant="success" className="text-[9px]">Paid</Badge>
                                                                    ) : payStatus === 'unpaid' || payStatus === 'pending' ? (
                                                                        <Badge variant="amber" className="text-[9px]">Pending Clearance</Badge>
                                                                    ) : null}

                                                                    {e.is_recalled && (
                                                                        <Badge variant="amber" className="text-[9px]">Recalled</Badge>
                                                                    )}

                                                                    {waitTimeStr && (
                                                                        <span className="text-[10px] text-zinc-500 font-mono">
                                                                            • Waited {waitTimeStr}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <EntryActions entry={e} onAction={handleAction} loadingId={loadingActionId} />
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>

                                {/* Skipped — recall path back into the queue */}
                                {snapshot.skipped.length > 0 && (
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold tracking-tight text-zinc-900">
                                            Skipped ({snapshot.skipped.length})
                                        </h3>
                                        <div className="divide-y divide-zinc-100 bg-white rounded-md border border-amber-200 overflow-hidden shadow-sm">
                                            {snapshot.skipped.map((e) => (
                                                <div key={e.id} className="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div className="flex items-center space-x-3">
                                                        <span className="font-mono font-bold text-base text-zinc-900">{e.queue_number}</span>
                                                        <Link
                                                            href={`/staff/patients/${e.patient?.id}`}
                                                            className="font-semibold text-xs text-zinc-900 hover:text-blue-600 hover:underline"
                                                        >
                                                            {e.patient?.full_name}
                                                        </Link>
                                                        <Badge variant="amber" className="text-[9px]">Skipped</Badge>
                                                    </div>
                                                    <EntryActions entry={e} onAction={handleAction} loadingId={loadingActionId} />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Cancelled — read-only, stays visible per queue policy */}
                                {snapshot.cancelled.length > 0 && (
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold tracking-tight text-zinc-900">
                                            Cancelled ({snapshot.cancelled.length})
                                        </h3>
                                        <div className="divide-y divide-zinc-100 bg-white rounded-md border border-zinc-200 overflow-hidden shadow-sm">
                                            {snapshot.cancelled.map((e) => (
                                                <div key={e.id} className="p-3 flex items-center space-x-3">
                                                    <span className="font-mono font-bold text-base text-zinc-400 line-through">{e.queue_number}</span>
                                                    <span className="font-semibold text-xs text-zinc-500">{e.patient?.full_name}</span>
                                                    <Badge variant="outline" className="text-[9px]">Cancelled</Badge>
                                                    {e.cancel_reason && (
                                                        <span className="text-[10px] text-zinc-400 italic">{e.cancel_reason}</span>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>

                    {/* Right Column (30% width): Expected Desk Arrivals */}
                    <div className="space-y-4">
                        <Card className="bg-white border border-zinc-200 shadow-sm">
                            <CardHeader className="pb-3 border-b border-zinc-100">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-xs font-semibold text-zinc-700 uppercase tracking-wider">
                                        Today&apos;s Desk Arrivals
                                    </CardTitle>
                                    <Badge variant="outline" className="font-mono text-[10px] float-right">{today_arrivals.length}</Badge>
                                </div>
                            </CardHeader>
                            <p className="text-[11px] text-zinc-500">Scheduled appointments requiring front-desk check-in.</p>
                            <CardContent className="pt-3">
                                {today_arrivals.length === 0 ? (
                                    <p className="text-xs text-zinc-400 italic text-center py-4">No pending scheduled arrivals for today.</p>
                                ) : (
                                    <div className="divide-y divide-zinc-100 max-h-[500px] overflow-y-auto">
                                        {today_arrivals.map((arrival) => (
                                            <div key={arrival.id} className="py-2.5 space-y-1.5">
                                                <div className="flex items-start justify-between">
                                                    <div>
                                                        <Link
                                                            href={`/staff/patients/${arrival.patient_id}`}
                                                            className="font-semibold text-xs text-zinc-900 hover:text-blue-600 hover:underline block"
                                                        >
                                                            {arrival.patient?.full_name}
                                                        </Link>
                                                        <span className="text-[10px] text-zinc-400 font-mono">
                                                            {arrival.patient?.patient_number || `PAT-#${arrival.patient_id}`}
                                                        </span>
                                                    </div>
                                                    <Badge
                                                        variant={arrival.status === 'pending_clearance' ? 'amber' : 'outline'}
                                                        className="text-[9px]"
                                                    >
                                                        {arrival.status === 'pending_clearance' ? 'Needs Payment' : 'Scheduled'}
                                                    </Badge>
                                                </div>

                                                <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                                    <span>{arrival.department?.name}</span>
                                                    {arrival.status === 'scheduled' ? (
                                                        <AsyncButton
                                                            onClick={() => handleCheckInArrival(arrival.id)}
                                                            loading={loadingActionId === `checkin-${arrival.id}`}
                                                            loadingText="Checking..."
                                                            variant="secondary"
                                                            className="px-2 py-0.5 text-[10px] font-semibold bg-zinc-900 text-white rounded hover:bg-zinc-800"
                                                        >
                                                            Check In
                                                        </AsyncButton>
                                                    ) : (
                                                        <Link
                                                            href="/staff/walk-in"
                                                            className="text-[10px] font-medium text-amber-800 hover:underline"
                                                        >
                                                            Clear Cash/POS →
                                                        </Link>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
